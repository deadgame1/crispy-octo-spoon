# crispy-octo-spoon

Live demo URL - https://crispy-octo-spoon.herokuapp.com/homepage

Requirements - php, symfony framework, postgres db, composer, yarn

Steps if you want to setup locally - 
1. clone the repo and cd into it
2. run the command `composer install`
3. create postgres sql database
4. enter the database credentials in the .env file 
   1. open .env file at the root of the project
   2. locate the line - `DATABASE_URL="postgresql://dbuser:dbpassword@127.0.0.1:5432/dbName?serverVersion=13&charset=utf8"`
   3. replace `dbuser` in the above line with actual database user
   4. replace `dbpassword` in the above line with actual database password
   5. replace `dbName` in the above line with actual name of the database 
   6. let everything else remain the same.
5. in terminal, run the command `symfony server:start`
6. go to https://127.0.0.1:8000/homepage