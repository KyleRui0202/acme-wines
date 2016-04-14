## Introduction
This is a simple mock wine ordering application that have the three basic endpoints:

* /orders/import
* /orders
* /orders/{id}

### /orders/import
This is the endpoint for people upload a orders CSV file containing the required order record. It also validate the orders according to 
a set of validation rules (explained later) and persist them with their validation results. It should also store a list of any validation failures the orders might have.

### /orders
This is the endpoint that returns all or part of the order records from the databases. There are several filters you can use as query parameters (including "valid", "limit", "offset", "field match" and "partial field match") to see specific order records of interest.

#### Validation rules:
1. No wine can ship to New Jersey, Connecticut, Pennsylvania, Massachusetts,
Illinois, Idaho or Oregon
2. Valid zip codes must be 5 or 9 digits
3. Everyone ordering must be 21 or older
4. Email address must be valid
5. The sum of digits in a zip code may not exceed 20 ("90210": 9+0+2+1+0 = 12)
6. Customers from NY may not have .net email addresses
7. If the state and zip code of the following record is the same as the 
current record, it automatically passes.

Any validation failures of each order will be stored in the database.

Within the returned JSON results, additional meta data including the effect filters and total number of redaults are also contained. 

### /orders/{id}
This is the endpoint that returns only one order record that has the order "id" if it exists, otherwise return a message showing no order of this "id".

## Installation
This application is built based on Lumen (a PHP micro-framework), which origins from Laravel. So you first need to make sure your server meets its basic requirement. For more details you may check [Lumen installation](http://lumen.laravel.com/docs). And then you may install this application as follows:

    cd path/to/root/folder/of/this/app
    composer install
    php artisan migrate

The last line is to build the database schema for this application. 

## Configuration
All the basic environment configuration options should be stored in `.env` file. If this file does not exist, you may find an `.env.example ` file. Then you can copy and paste it as `.env` and edit the configuration options including the debugging, logging, databases, queue driver, etc.

Moreover, there are also configuration files specified for this application in the `config/` folder. One is `ordercsv` which contains basic configuration about the order CSV to be uploaded, and the other one is `validation`, in which you may configure the validators to apply to order importing.

## Usage
You may use `curl` command to connect to and use this application from the console, for example,

* **/orders/import**: `curl -i -F orders=@[theordercsvfile] [hostname]/orders/import`
* **/orders**: `curl -i [hostname]/orders?valid=1&limit=30&state=ny$name_partial_match=Dav`
* **/orders/{id}**: `curl -i [hostname]/orders/3`

## Application Structure
This application is working based on [Lumen installation](http://lumen.laravel.com/). The core code are located in the `app/` folder, in which I implemented the routing (`app/Http/routes.php`), controller (`app/Http/Controllers/OrdersController.php`) and model (`app/Order.php`).

I also wrap two independent tasks (result filtering of `orders` and order importing) into `jobs`, and dispatch them from the controller.

## Testing
There are functional tests based on [PHPUnit](https://phpunit.de/) for all the three endpoints included in the `tests/functional` folder. So you may use **phpunit** to run those tests

Actually I haven't completed the testing for (partial) field match for the `/orders` endpoint, and some other features (e.g., processing the order importing work in a queue) are also not be covered in the test. I probably will keep working on this.

## Acknowledgement
Chris, thank you for your patience! And I do apologze for the delayed submission of this code challenge. Actually I did try my best to complete it not just as a test but also make it something in which a real-world project may be built upon in the future. So I spent quite some time on testing and debugging it as well as introduced additional features. I'm sorry again for any inconvinience.
  

### License

The Lumen framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
