<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use \SplFileInfo;
use \SplFileObject;

class OrdersImportWithValidCsvTest extends TestCase
{
    // Wrap every test case in a database transaction
    // to reset the database after each test
    use DatabaseTransactions;

    /*
     * The directory to store all the order csvs for testing
     *
     * @var string
     */
    protected $csvDirectory = 'order_csvs_for_test/';

    /**
     * A test for the endpoint "orders/import"
     * with all valid orders
     *
     * @test
     * @dataProvider orderCsvWithOnlyValidOrdersProvider
     * @param string $csvFilename
     * @return void
     */
    public function only_valid_orders_are_imported($csvFilename)
    {
        $this->importACopiedOrderCSV($csvFilename);
        $this->seeJson(['import_status' => 'File uploaded successfuly']);
        
        $csvPathname = $this->csvDirectory.$csvFilename;
        $parsedOrders = $this->readOrderCsvFile($csvPathname);

        foreach ($parsedOrders as $parsedOrder) {
            //dd(App\Order::all());
            $parsedOrders['valid'] = true;
            $this->seeInDatabase('orders', $parsedOrder);
        }
    }

    /*
     * Provide the file name of the order csv
     * with all valid orders in the same directory
     */
    public function orderCsvWithOnlyValidOrdersProvider()
    {
        return [
            'no_order' => ['valid_orders_with_no_order.csv'],
            'single_order' => ['valid_orders_with_single_order.csv'],
            'multiple_orders' => ['valid_orders_with_multiple_orders.csv']
        ];
    }

    /** A test for the endpoint "orders/import"
     * with empty value for required fields in order 
     *
     * @test
     * @dataProvider orderCsvWithEmptyValueForRequiredFieldsProvider
     * @param string $csvFilename
     * @return void
     */
    public function orders_with_empty_value_for_required_fields_are_imported($csvFilename, $emptyField)
    {
        $this->importACopiedOrderCSV($csvFilename);
        $this->seeJson(['import_status' => 'File uploaded successfuly']);
        
        $csvPathname = $this->csvDirectory.$csvFilename;
        $parsedOrders = $this->readOrderCsvFile($csvPathname);

        foreach ($parsedOrders as $parsedOrder) {
            unset($parsedOrder[$emptyField]);
            $parsedOrder['valid'] = false;
            $parsedOrder['validation_errors'] = json_encode([[
                'rule' => 'Required'.ucfirst($emptyField),
                'message'=> 'The '.$emptyField.' is missing',
            ]]);

            $this->seeInDatabase('orders', $parsedOrder);
        }
    }

    /*
     * Provide the file name of the order csv
     * with orders missing a specific field
     */
    public function orderCsvWithEmptyValueForRequiredFieldsProvider()
    {
        return [
            'empty_name' => ['orders_with_empty_name.csv', 'name'],
            'empty_email' => ['orders_with_empty_email.csv', 'email'],
            'empty_state' => ['orders_with_empty_state.csv', 'state'],
            'empty_zipcode' => ['orders_with_empty_zipcode.csv', 'zipcode'],
            'empty_birthday' => ['orders_with_empty_birthday.csv', 'birthday'],
        ];
    }

    /**
     * A test for the endpoint "orders/import"
     * with invalid emails
     *
     * @test
     * @dataProvider orderCsvWithInvalidEmailsProvider
     * @param string $csvFilename
     * @return void
     */
    public function orders_with_invalid_emails_are_imported($csvFilename)
    {
        $this->importACopiedOrderCSV($csvFilename);
        $this->seeJson(['import_status' => 'File uploaded successfuly']);
        
        $csvPathname = $this->csvDirectory.$csvFilename;
        $parsedOrders = $this->readOrderCsvFile($csvPathname);

        foreach ($parsedOrders as $parsedOrder) {
            //dd(App\Order::all());
            $parsedOrder['valid'] = false;
            $parsedOrder['validation_errors'] = json_encode([[
                'rule' => config('validation.email.type.rule_title'),
                'message'=>config('validation.email.type.error_message'),
            ]]);
            $this->seeInDatabase('orders', $parsedOrder);
        }
    }

    /*
     * Provide the file name of the order csv with all
     * orders containing invalid emails in the same directory
     */
    public function orderCsvWithInvalidEmailsProvider() {
        return [
            'invalid_email_type' => ['orders_with_invalid_email_type.csv'],
        ];
    }

    /**
     * A test for the endpoint "orders/import"
     * with the states not allowed.
     *
     * @test
     * @dataProvider orderCsvWithNotAllowedStatesProvider
     * @param string $csvFilename
     * @return void
     */
    public function orders_with_not_allowed_states_are_imported($csvFilename)
    {
        $this->importACopiedOrderCSV($csvFilename);
        $this->seeJson(['import_status' => 'File uploaded successfuly']);
        
        $csvPathname = $this->csvDirectory.$csvFilename;
        $parsedOrders = $this->readOrderCsvFile($csvPathname);

        foreach ($parsedOrders as $parsedOrder) {
            //dd(App\Order::all());
            $parsedOrder['valid'] = false;
            $parsedOrder['validation_errors'] = json_encode([[
                'rule' => config('validation.state.allowed_states.rule_title'),
                'message'=>config('validation.state.allowed_states.error_message'),
            ]]);
            $this->seeInDatabase('orders', $parsedOrder);
        }
    }

    /*
     * Provide the file name of the order csv with all
     * the orders containing the states not allowed for ordering
     */
    public function orderCsvWithNotAllowedStatesProvider() {
        return [
            'not_allowed_states' => ['orders_with_not_allowed_states.csv'],
        ];
    }

    /**
     * A test for the endpoint "orders/import"
     * with invalid zipcode
     *
     * @test
     * @dataProvider orderCsvWithInvalidZipcodeProvider
     * @param string $csvFilename
     * @param string $ruleType
     * @return void
     */
    public function orders_with_invalid_zipcode_are_imported($csvFilename, $ruleType)
    {
        $this->importACopiedOrderCSV($csvFilename);
        $this->seeJson(['import_status' => 'File uploaded successfuly']);
        
        $csvPathname = $this->csvDirectory.$csvFilename;
        $parsedOrders = $this->readOrderCsvFile($csvPathname);

        foreach ($parsedOrders as $parsedOrder) {
            if ($ruleType === 'digit_sum_of_zipcode.range_limit') {
                //dd(App\Order::all());
            }
            $parsedOrder['valid'] = false;
            $parsedOrder['validation_errors'] = json_encode([[
                'rule' => config('validation.'.$ruleType.'.rule_title'),
                'message'=>config('validation.'.$ruleType.'.error_message'),
            ]]);
            $this->seeInDatabase('orders', $parsedOrder);
        }
    }

    /*
     * Provide the file name of the order csv with all
     * orders containing invalid zipcode
     */
    public function orderCsvWithInvalidZipcodeProvider() {
        return [
            'zipcode_pattern' => [
                'orders_with_invalid_zipcode_pattern.csv', 'zipcode.pattern'],
            'zipcode_digit_sum' => [
                'orders_with_invalid_zipcode_digitsum.csv', 'digit_sum_of_zipcode.range_limit'],
        ];
    }

    /**
     * A test for the endpoint "orders/import"
     * with invalid birthday
     *
     * @test
     * @dataProvider orderCsvWithInvalidBirthdayProvider
     * @param string $csvFilename
     * @param string $ruleType
     * @return void
     */
    public function orders_with_invalid_birthday_are_imported($csvFilename, $ruleType)
    {
        $this->importACopiedOrderCSV($csvFilename);
        $this->seeJson(['import_status' => 'File uploaded successfuly']);
        
        $csvPathname = $this->csvDirectory.$csvFilename;
        $parsedOrders = $this->readOrderCsvFile($csvPathname);

        foreach ($parsedOrders as $parsedOrder) {
            $parsedOrder['valid'] = false;
            if ($ruleType === 'birthday.type') {
                unset($parsedOrder['birthday']);
            }

            $parsedOrder['validation_errors'] = json_encode([[
                'rule' => config('validation.'.$ruleType.'.rule_title'),
                'message'=>config('validation.'.$ruleType.'.error_message'),
            ]]);
          
            $this->seeInDatabase('orders', $parsedOrder);
        }
    }

    /*
     * Provide the file name of the order csv with all
     * orders containing invalid birthday
     */
    public function orderCsvWithInvalidBirthdayProvider() {
        return [
            'birthday_format' => [
                'orders_with_invalid_birthday_format.csv', 'birthday.type'],
            'age_restriction' => [
                'orders_with_restricted_age.csv', 'birthday.max_birth_date'],
        ];
    }    

    /**
     * A test for the endpoint "orders/import"
     * with one invalid order followed by an order
     * which has the same state and zipcode
     *
     * @test
     * @dataProvider orderCsvWithOrderFollowedByOrderWithSameStateAndZipcodeProvider
     * @param string $csvFilename
     * @return void
     */
    public function orders_with_order_followed_by_order_with_same_state_and_zipcode_are_imported($csvFilename)
    {
        $this->importACopiedOrderCSV($csvFilename);
        $this->seeJson(['import_status' => 'File uploaded successfuly']);
        
        $csvPathname = $this->csvDirectory.$csvFilename;
        $parsedOrders = $this->readOrderCsvFile($csvPathname);

        foreach ($parsedOrders as $parsedOrder) {
            //dd(App\Order::all());
            $parsedOrder['valid'] = true;
            $this->seeInDatabase('orders', $parsedOrder);
        }
    }

    /*
     * Provide the file name of the order csv with
     * the order followed by another order which has
     * the same state and zipcode
     */
    public function orderCsvWithOrderFollowedByOrderWithSameStateAndZipcodeProvider()
    {
        return [
            'orders_wth_same_state_and_zipcode' => [
                'orders_with_same_state_zipcode.csv'
            ],
        ];
    }

    /*
     * Copy an order csv and send a post request importing
     * that copy to the "orders/import" endpoint
     *
     * @param string $csvFilename
     * @return void
     */
    protected function importACopiedOrderCsv($csvFilename)
    {
        $csvPathname = __DIR__.'/'.$this->csvDirectory.$csvFilename;
        $copiedCsvPathname = __DIR__.'/'.$this->csvDirectory.'copied_'.$csvFilename;
        copy($csvPathname, $copiedCsvPathname);
        $csvFileToUpload = new UploadedFile($copiedCsvPathname,
            $csvFilename, 'text/csv', null, null, true);
        //dd($csvFileToUpload->guessExtension());
        $this->call('POST', 'orders/import',
            [], [], ['orders' => $csvFileToUpload]);
    }

     
    /*
     * Read order csv file and parse order info.
     *
     * @param string $pathname
     * @return array
     */
    protected function readOrderCsvFile($pathname)
    {
        $file = new SplFileInfo(__DIR__.'/'.$pathname);
        $fileObj = $file->openFile();
        $fileObj->setFlags(SplFileObject::DROP_NEW_LINE |
            SplFileObject::READ_AHEAD |
            SplFileObject::SKIP_EMPTY |
            SplFileObject::READ_CSV);
        $fileObj->setCsvControl(config('ordercsv.delimiter'));
        $fields = $fileObj->fgetcsv();

        $records = [];
        while (!$fileObj->eof()) {
            $fieldValues = $fileObj->fgetcsv();
            if (!is_array($fieldValues)) {
                continue;
            }
            $record = array_combine($fields, $fieldValues);
            if (array_key_exists('birthday', $record)) {
                $record['birthday'] = ($tmpDate = date_create_from_format(
                    config('ordercsv.birthday_format'),
                    $record['birthday'])) ? $tmpDate->format('Y-m-d') :
                    '0000-00-00';
            }
            if (array_key_exists('email', $record)) {
                $record['email'] = strtolower($record['email']);
            }
            if (array_key_exists('state', $record)) {
                $record['state'] = strtoupper($record['state']);
            }
            if (array_key_exists('zipcode', $record)) {
                $record['zipcode'] = str_replace('*', '-', $record['zipcode']);
            }

            $records[] = $record;
        }

        return $records;
    }

}
