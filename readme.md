## Introduction
This is a simple mock wine ordering application that has the three basic endpoints:

* /orders/import
* /orders
* /orders/{id}


### /orders/import
This is the endpoint for people to upload an order CSV file containing the required order records. It also validates the orders according to 
a set of validation rules (listed below) and persist them with their validation results. It should also store a list of any validation failures the orders might have.

#### Default Validation Rules:
1. The fields "id", "name", "email", "state", "zipcode" and "birthday" are required in the csv. If a field is missing from the headers, then the import should not process. If a field value is missing within an order record, the related validation failure should be attached.
2. No wine can ship to **New Jersey (NJ)**, **Connecticut (CT)**, **Pennsylvania (PA)**, **Massachusetts (MA)**, **Illinois (IL)**, **Idaho (ID)** or **Oregon (OR)**
3. Valid zip codes must be 5 or 9 digits
4. Everyone ordering must be 21 or older
5. Email address must be valid
6. The sum of digits in a zip code may not exceed 20 ("90210": 9+0+2+1+0 = 12)
7. Customers from NY may not have .net email addresses
8. If the state and zip code of the following record is the same as the
current record and are not blank, it automatically passes. (Notice: the validation failures for the record itself will still be attached though)

**Notice**: Each of the above rules is configurable **except** Rule 1 and 8 (more details in **Configuration** section), and the validation failures will be attached to each corresponding order in the database.


### /orders
This is the endpoint that returns all or part of the order records from the database. There are several filters you can use as query parameters (including "valid", "limit", "offset", "field match" and "partial field match") to see specific order records of interest.

Within the returned JSON results, additional metadata including the effect filters and the total number of results are also contained. 


### /orders/{id}
This is the endpoint that returns only one order record that has the order "id" if it exists, otherwise return a message showing no order of this "id".



## Installation
This application is built based on Lumen (a PHP micro-framework), which origins from Laravel. So you first need to make sure your server meets its basic requirement. For more details you may check [Lumen installation](http://lumen.laravel.com/docs). And then you may install this application as follows:

    cd path/to/root/folder/of/this/app
    composer install
    php artisan migrate

The last line is to build the database schema using the database migrations for this application.



## Configuration
All the basic environment configuration options should be stored in `.env` file. If this file does not exist, you may find an `.env.example ` file, copy and paste it as `.env` and edit the configuration options for debugging, logging, databases, queue driver, etc.

Moreover, there are also configuration files specified for this application in the `config/` folder. One is `ordercsv` which contains basic configuration about the order CSV to be uploaded, and the other one is `validation`, in which you may configure the validators and change their default validations rules for order importing.

To launch the application you also need to set the document root to the `public/` folder for your server configiration.


## How To Use It
You may use `curl` command to connect and send requests to the url of the three endpoints from Linux/Unix console. Assume that this application is running on the `localhost` and the port `8888`.


### /orders/import
You may use the following command to import the "order_csv_file.csv":

    curl -i -F orders=@[ordercsvfile.csv] localhost:8888/orders/import

where the option `-i` makes the result display display protocol headers.

#### Queueable Order Importing
You are allowed to dispatch the order importing task to a queueable job which can be processed asynchronously. To enable this feature, you need to set `QUEUE_DRIVER=database` in the environment configuration file `.env`, and then run the queue listener or worker to process the jobs on the queue:

    // Run new jobs as they are pushed onto the queue
    php artisan queue:listen

You may use a process monitor such as [Supervisor](http://supervisord.org/) to ensure that the queue listener does not stop running. You may also use queue worker to only process the first job on the queue each time:

    php artisan queue:work


### /orders
This endpoint has three categories of filters to retrieve orders:

* Constraint: `valid`, `limit` and `offset`
* Field Match: `name`, `email`, `state` and `zipcode`
* Field Partial Match: `name`, `email` and `zipcode`

**Notice**: you may disable any of the above filters from within the job `OrderFilterJob.php` by modifying its property `allowedFilters`.

#### Constraint Filters
You are allowed to retrieve only valid orders by setting the url query parameter `valid` to `true`, `on`, `yes` or `1`:

    curl -i localhost:8888/orders?valid=true
    curl -i localhost:8888/orders?valid=on
    curl -i localhost:8888/orders?valid=yes
    curl -i localhost:8888/orders?valid=1

or retrieve only invalid orders by setting `valid` to `false`, `off`, `no`, `0` or ``:

    curl -i localhost:8888/orders?valid=false
    curl -i localhost:8888/orders?valid=off
    curl -i localhost:8888/orders?valid=no
    curl -i localhost:8888/orders?valid=0
    curl -i localhost:8888/orders?valid=

You may also retrieve part of the results as you want by setting the filter `limit` and/or `offset`:

    // Only retrieve 10 valid orders 
    curl -i localhost:8888/orders?valid=1&limit=10

    // Skip the first 5 invalid orders and retrieve the rest of invalid orders 
    curl -i localhost:8888/orders?valid=0&offset=5

**Notice**: The filters `limit` and `offset` sort the orders in ascending order according to order "id" first before coming into effect.

#### Field Match Filters
You are allowed to retrieve the orders that have specific values (case insensitive) for some fields including "name", "email", "state" and "zipcode":

    // Retrieve all the orders whose orderer's name is "David"
    curl -i localhost:8888/orders?name=David

    // Retrieve all the orders whose orderer's email address is "foo@example.com"
    curl -i localhost:8888/orders?email=foo@example.com

    // Retrieve all the orders that come from the state "NY"
    curl -i localhost:8888/orders?state=NY

    // Retrieve all the orders that come from an address of which zipcode is "13210-1242"
    curl -i localhost:8888/orders?zipcode=13210-1242  

#### Field Partial Match Filters
You are allowed to retrieve the orders that have some fields (including "name", "email", "state" and "zipcode") that partially match specific values (case insensitive):

    // Retrieve all the orders whose orderer's name contains "Dav"
    curl -i localhost:8888/orders?name_partial_match=Dav

    // Retrieve all the orders whose orderer's email address contains "example.com"
    curl -i localhost:8888/orders?email_partial_match=example.com

    // Retrieve all the orders that come from an address of which zipcode contains "1321"
    curl -i localhost:8888/orders?zipcode_partial_match=1321


Of course you may combine any of the filters for the endpoint `/orders`, for example, `curl -i localhost:8888/orders?valid=1&limit=30&state=ny$name_partial_match=Dav`


### /orders/{id}
You are allowed to retrieve an order of specified order "id":

    // Retrieve the order of id=3
    curl -i localhost:8888/orders/3



## Application Structure
This application is working based on [Lumen installation](http://lumen.laravel.com/). The core code are located in the `app/` folder, in which I implemented the routing (`app/Http/routes.php`), controller (`app/Http/Controllers/OrdersController.php`) and model (`app/Order.php`). I also wrap two independent tasks (order filtering and order importing) into two jobs (the former is synchronous and the latter is queueable) respectively, and dispatch them from the controller.


### app/Http/routes.php
All the routes for this application (the three endpoints) are defined in `app/Http/routes.php`.


### app/Http/Controllers/OrdersController.php
Define the request handling logic for all the three endpoints:

* **/orders/import**: defined in the method `OrdersController@import`, which validates uploaded csv file, dispatches order importing job and return the importing status.
* **/orders**: defined in the method `OrdersController@index`, which retrieves filters from the query string of url, dispatches order filtering job and retruns the filtered orders with metadata.
* **/orders/{id}**: defined in the method `OrdersController@show`, which determines if the order of the specified order id exists and returns the corresponding order or information.


### app/Order.php
Define the `Order` model to manage the order record and interact with the corersponding table in the database. It conatains but not limited to the following parts:

* Local scopes (e.g., `scopeFieldMatch`): apply order index filters to corresponding queries
* Field mutators (e.g., `setNameAttribute`): parse and validate the imported order record according to a specific field
* Attribute Casting (`$casts`): convert specific fields to the specified data types.



## Testing
There are functional tests based on [PHPUnit](https://phpunit.de/) for all the three endpoints included in the `tests/functional` folder. So you may use **phpunit** to run those tests upon a cleaned-up database. 

    // Run this command from the root folder to load test configuration from phpunit.xml.
    // If "phpunit" is not available globally, you may try "vendor/bin/phpunit" instead
    phpunit tests/functional/


### Notice
* You have to set `QUEUE_DRIVER` to `sync` in `phpunit.xml` before running the order importing test. 

* You may need to setup another database for testing only to avoid database clean-up risk and other database operation risks during testing:

    1. Set `DB_DATABASE` manually to the test database (e.g., "acmewines_testing").
    2. Run `php artisan migrate` to setup database schema inclduing the table "orders" and tables for queue processing.
    3. Reset `DB_DATABASE` to the product database.
    4. Set the environment variable "DB_DATABASE" to the test database accordingly in `phpunit.xml`.

Actually not all features of this application (e.g., processing the order importing task asynchronously in a queue) are covered in the test. I probably will keep working on them as well as critical unit testing.


  
## License

The Lumen framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
