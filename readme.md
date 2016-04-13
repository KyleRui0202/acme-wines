## Introduction
This is a mock wine order processing application that have three basic endpoints:
* /orders/import
* /orders
* /orders/{id}

### /orders/import
This is the endpoint for people upload a orders CSV file containing the required order record. It also validate the orders according to 
a set of validation rules (explained later) and persist them with their validation result. It should also store a list of any validation failures the orders might have.

### /orders
This is the endpoint that returns all or part of the order records from the databases. There are several filters we can use as query parameters (including "valid", "limit", "offset", "field match" and "partial field match") to see specific order records of interest.

### /orders/id
This is the endpoint that returns only one order record that has the order "id" if it exists.

## Installation
This application is built based on Lumen (a PHP micro-framework), which origin from Laravel. So you first need to make sure your server meets its basic requirement. For more details you may check [Lumen installation](http://lumen.laravel.com/docs). And then you may install this application as follows:

    cd path/to/root/folder/of/this/app
    composer install
    php artisan migrate

The last line is to build the database schema for this application. 

## Configuration
All the basic environment configuration options should be stored in `.env` file. If this file does not exist, you may find an `.env.example ` file. Then you copy and past it as `.env` and edit the configuration options including the debugging, logging, databases, queue driver, etc.

Moreover, there are also configuration files specified for this application in the `config` folder. One is `ordercsv` which contains basic configuration about the order CSV to be uploaded, and the other one is `validation`, in which you  may configure the validators to apply for orders.

## Usage
You may use `curl` command to connect to and use this application:

* **/orders/import**: `curl -i -F orders=@[theordercsvfile] [hostname]/orders/import`
* **/orders**: `curl -i [hostname]/orders?valid=1&limit=30&state=ny$name_partial_match=Dav`
* **/orders/{id}** `curl -i [hostname]/orders/3`

### Application Structure
This application is working based on [Lumen installation](http://lumen.laravel.com/). The core code are located in the `app/` folder, in which I implemented the routing (`app/Http/route.php`), controller (`app/Http/Controllers/OrdersController`) and model (`app/Order.php`).

I also wrap two independent tasks (result filtering of `orders` and order importing) into `jobs`, and dispatch them from the controller.

### Testing
There are functional tests based on [PHPUnit](https://phpunit.de/) for all the three endpoints included in the `tests/functional` folder. So you may use **phpunit** to run those tests

Actually I haven't completed the testing for (partial) field match for the `/orders` endpoint, and some other features (e.g., processing the order importing work in a queue) are also not be covered in the test. I probably will keep working on this.

### Acknowledgement
Chris, thank you for your patience! And I do apologze again for the delayed submission of this code challenge. Actually I did try my best to complete it not just as a test but also make it something in which a real-world project may be built upon in the future. So I spent quite some time on testing and debugging it as well as introduced additional features. 
  

### License

The Lumen framework is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
