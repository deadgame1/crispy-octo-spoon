const loader= document.getElementById('loader')
const help= document.getElementById('help')
const noStats = document.getElementById('noStats')
const stats = document.getElementById('stats')
const stockNameEle = document.getElementById('stockName')
let noOfstocks = 200
$("#uploadcsv").click(function(){
    loader.style.display='flex';
    const formData = new FormData();
    const fileField = document.querySelector('input[type="file"]');
    formData.append('csv', fileField.files[0]);

    fetch('/postCsv', {
        method: 'POST',
        body: formData
    })
    .then(
        response => {
            return response.json();
        }
    )
    .then(result => {
        let x = JSON.stringify(result)
        let msg = JSON.parse(x);
        stockNameEle.dataset.stocknames = msg.allStocks
        loader.style.display='none';
        alert('Message: '+JSON.stringify(msg.message))
        console.log('Message:', JSON.stringify(result));
    })
    .catch(error => {
        loader.style.display='none';
        alert('Error :(')
        console.error('Error:', JSON.stringify(error));
    });
});
$("#getstats").click(function(){
    loader.style.display='flex'
    const sd = document.querySelector('#startDate').value
    const ed = document.querySelector('#endDate').value
    const stockName = document.querySelector('#stockName').value
    const stockQuantity = document.querySelector('#quantity').value
    
    if(!stockName){ //@todo uncomment
        help.innerHTML = 'Please enter stock name';
        loader.style.display='none';
        return;
    }
    if(!sd){
        help.innerHTML = 'Please enter start date';
        loader.style.display='none';
        return;
    }
    if(!ed){
        help.innerHTML = 'Please enter end date';
        loader.style.display='none';
        return;
    }
    let sdo = new Date(sd);
    let edo = new Date(ed);
    let today = new Date();
    if(sdo > edo){
        help.innerHTML = 'Start date should be less than End date';
        loader.style.display='none';
        return;
    }
    help.innerHTML = '';
    fetch('/stockAdvice?'+ new URLSearchParams({
        stock: stockName,//@todo uncomment
        startDate: sdo.toISOString().slice(0,10),
        endDate: edo.toISOString().slice(0,10),
        noOfStocks: stockQuantity ? stockQuantity : 200,
        // stock: 'aapl',//@todo delete hardcoded
        //startDate: '2000-03-01',
        //endDate: '2022-03-01'
    }))
    .then(response => response.json())
    .then(result => {
        loader.style.display='none';
        let x = JSON.stringify(result)
        let msg = JSON.parse(x);
        if(msg.error){
            stats.style.display='none';
            noStats.style.display='block';
            noStats.innerHTML = msg.error;
            console.log('error')
            return;
        }else{
            //success
            console.log('success')
            stats.style.display="block";
            noStats.style.display="none";

            //Draw UI
            drawStatsUi(msg)
            drawTableUi(msg)

            let checkbox = document.getElementById("chk")
            let tableParent = document.querySelector('#stats-sec')
            if(tableParent.dataset.toggletable == 1){
                checkbox.checked = false
                tableToggle(false)
            }else{
                checkbox.checked = true
                tableToggle(true)
            }
        }
    })
    .catch(error => {
        loader.style.display='none';
        stats.style.display='none';
        noStats.style.display='block';
        console.log('catch')
        console.log(error)
        noStats.innerHTML = 'Error';
    });
});

function drawStatsUi(msg)
{
    document.querySelector('#statsFor').textContent = `Stock Name - ${msg.name}`
    let list = document.querySelector('#txn2')
    list.innerHTML=''
    let html = '';
    html += '<table><tbody><tr>'
    html += `<td><strong>Total Profit : </strong>Rs ${msg.totalProfit}</td>`
    html += `<td><strong>Standard Deviation : </strong>${msg.standardDeviation}</td>`
    html += `<td><strong>Mean Price : </strong>${msg.meanPrice}</td>`
    html+= `<td style="text-align:right;"><div style="margin: 0 0 0 auto;">Toggle Table View  &nbsp;&nbsp;<label class="switch">
        <input id="chk" type="checkbox">
        <span class="slider round"></span>
    </label></div></td>`;
    html += '</tr></tbody></table>'
    list.innerHTML = html

    document.getElementById("chk").addEventListener("click", function(){
        tableToggle(this.checked)
    });
}

function tableToggle(boolVal){
    let table1 = document.querySelector('.table1')
    let table2 = document.querySelector('.table2')
    let tableParent = document.querySelector('#stats-sec')
    if(table1 && table2){
        if(boolVal){
            table1.style.display = 'none';
            table2.style.display = 'table';
            tableParent.dataset.toggletable=2;
        }else{
            table1.style.display = 'table';
            table2.style.display = 'none';
            tableParent.dataset.toggletable=1;
        }
    }
}

function drawTableUi(msg)
{
    let tableParent = document.querySelector('#stats-sec')
    tableParent.innerHTML=''
    let html='';

    //table1
    html += `<table class="table table-bordered table-sm table1 center">
        <thead >
            <tr style="font-size:15px;">
                <th scope="col" rowspan="2">Transaction Number</th>
                <th scope="col" colspan="2">Buy</th>
                <th scope="col" colspan="2">Sell</th>
                <th scope="col" rowspan="2">Profit per stock</th>
                <th scope="col" rowspan="2">Profit <small id="stocksele">for ${noOfstocks} stocks</small></th>
            </tr>
            <tr>
            <th scope="col">Date</th>
            <th scope="col">Price</th>
            <th scope="col">Date</th>
            <th scope="col">Price</th>
            </tr>
        </thead><tbody id='txn'>`;

    let k=0;let l=0;
    msg.transactionalData.forEach(element => {
        let eleclass = (l % 2 == 1) ? 'tr1' : 'tr2';
        html += `<tr class=${eleclass}>
            <td>#${k++}</td>
            <td>${element.buyDate}</td>
            <td>${element.buyPrice}</td>
            <td>${element.sellDate}</td>
            <td>${element.sellPrice}</td>
            <td>${element.profit}</td>
            <td>${element.netProfitForThisTxn}</td>
        </tr>`;
        l++
    });
    html += `</tbody>`;
    html += `<tr><td></td><td></td><td></td><td></td><td></td><td></td><td id="totalProfitEle">${msg.totalProfit} <small>(Total Profit)</small></td></tr>`;
    html += `</table>`;

    //table2
    html += `<table class="table table-bordered table-sm table2 center">
        <thead>
            <tr style="font-size:15px;">
                <th scope="col">Transaction Number</th>
                <th scope="col">Transaction Type</th>
                <th scope="col">Date</th>
                <th scope="col">Price per stock</th>
                <th scope="col">Profit per stock</th>
                <th scope="col">Profit <small id="stocksele">for ${noOfstocks} stocks</small></th>
            </tr>
        </thead><tbody id='txn'>`;

    let i=0;let j=0;
    msg.transactionalData.forEach(element => {
        let eleclass = (j % 2 == 1) ? 'tr1' : 'tr2';
        html += `<tr class=${eleclass}>
            <td>#${i++}</td>
            <td>Buy</td>
            <td>${element.buyDate}</td>
            <td>${element.buyPrice}</td>
            <td></td>
            <td></td>
        </tr>`;
        html += `<tr class=${eleclass}>
            <td>#${i++}</td>
            <td>Sell</td>
            <td>${element.sellDate}</td>
            <td>${element.sellPrice}</td>
            <td>${element.profit}</td>
            <td>${element.netProfitForThisTxn}</td>
        </tr>`;
        j++
    });
    html += `<tr><td></td><td></td><td></td><td></td><td></td><td id="totalProfitEle">${msg.totalProfit} <small>(Total Profit)</small></td></tr>`;
    html += `</tbody>`;
    html += `</table>`;


    tableParent.innerHTML = html;
}



function autocomplete(inp, arr) {
    var currentFocus;
    inp.addEventListener("input", function(e) {
        let a, b, i, val = this.value;
        closeAllLists();
        if (!val) { return false;}
        currentFocus = -1;

        a = document.createElement("DIV");
        a.setAttribute("id", this.id + "autocomplete-list");
        a.setAttribute("class", "autocomplete-items");

        this.parentNode.appendChild(a);

        for (i = 0; i < arr.length; i++) {
            if (arr[i].substr(0, val.length).toUpperCase() == val.toUpperCase()) {
                b = document.createElement("DIV");
                /*make the matching letters bold:*/
                b.innerHTML = "<strong>" + arr[i].substr(0, val.length) + "</strong>";
                b.innerHTML += arr[i].substr(val.length);
                b.innerHTML += "<input type='hidden' value='" + arr[i] + "'>";
                    b.addEventListener("click", function(e) {
                    inp.value = this.getElementsByTagName("input")[0].value;
                    closeAllLists();
                });
                a.appendChild(b);
            }
        }
    });

    inp.addEventListener("keydown", function(e) { //listening to keyboard keypresses
        var x = document.getElementById(this.id + "autocomplete-list");
        if (x) x = x.getElementsByTagName("div");
        if (e.keyCode == 40) { //down key
            currentFocus++;
            addActive(x);
        } else if (e.keyCode == 38) { //up key
            currentFocus--;
            addActive(x);
        } else if (e.keyCode == 13) { //enter key
            e.preventDefault();
            if (currentFocus > -1) {
                if (x) x[currentFocus].click();
            }
        }
    });
    function addActive(x) {
        if (!x) return false;
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        x[currentFocus].classList.add("autocomplete-active");
    }
    function removeActive(x) {
        for (let i = 0; i < x.length; i++) {
            x[i].classList.remove("autocomplete-active");
        }
    }
    function closeAllLists(elmnt) {
        var x = document.getElementsByClassName("autocomplete-items");
        for (let i = 0; i < x.length; i++) {
        if (elmnt != x[i] && elmnt != inp) {
            x[i].parentNode.removeChild(x[i]);
        }
    }
    }
    document.addEventListener("click", function (e) {
        closeAllLists(e.target); //if someone clicks then close the list
    });
}
autocomplete(stockNameEle, stockNameEle.dataset.stocknames.split(","));