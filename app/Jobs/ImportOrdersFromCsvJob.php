<?php

namespace App\Jobs;

use App\Order;
//use Symfony\Component\HttpFoundation\File\UploadedFile;
use \SplFileInfo;
use \SplFileObject;
use Log;

class ImportOrdersFromCsvJob extends Job
{
    /*
     * The pathname of the temporary CSV file.
     * 
     * @var string
     */
    protected $filePathname;

    /*
     * Required order fields.
     * 
     * @var array
     */
    protected $requiredFields = ['id', 'name', 'email',
        'state', 'zipcode', 'birthday'];

    /**
     * Create a new job instance.
     *
     * @param string $filePathname
     * @return void
     */
    public function __construct($filePathname)
    {
        $this->filePathname = $filePathname;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $file = new SplFileInfo($this->filePathname);
        $fileObj = $file->openFile();
        $fileObj->setFlags(SplFileObject::DROP_NEW_LINE |
            SplFileObject::READ_AHEAD |
            SplFileObject::SKIP_EMPTY |
            SplFileObject::READ_CSV);
        $fileObj->setCsvControl(config('ordercsv.delimiter'));
        $fields = $fileObj->fgetcsv();

        $lackFields = array_diff($this->requiredFields, $fields);
        if (count($lackFields) > 0) {
            Log::error('Fail to import orders: lack of required fields in uploaded CSV!',
                ['uploaded_file' => $fileObj->getRealPath(),
                'missing_fields' => $lackFields]);
        }
        else {
            $prevOrder = null;
            while (!$fileObj->eof()) {
                $fieldValues = $fileObj->fgetcsv();
                $record = array_combine($fields, $fieldValues);
                $curOrder = Order::updateOrCreate([
                    'id' => $record['id']], $record);
                if (!is_null($prevOrder) && $prevOrder->valid == false) {
                    $this->validateRecordByStateZipcode($prevOrder, $curOrder);
                }
                $prevOrder = $curOrder;
	    }

            // Delete the uploaded csv file after
            // importing it into the database
            $fileObj = null;
            $file = null;
            unlink($this->filePathname);
        }
    }

    /*
     * Handle a job failure.
     *
     * @return void
     */
    public function failed() {
         Log::error('Fail to import all the orders in the uploaded order csv!',
                ['uploaded_file' => $this->filePathname]);
    }

    /*
     * A target order record is valid if it has the same state and zipcode
     * as the referred record upon order importing.
     *
     * @param Order $targetOrder
     * @param Order $referredOrder
     * @return void
     */
    protected function validateRecordByStateZipcode(Order $targetOrder, Order $referredOrder) {
        if (!empty($targetOrder->state) && !empty($targetOrder->zipcode) &&
            $targetOrder->state === $referredOrder->state &&
            $targetOrder->zipcode === $referredOrder->zipcode) {
            $targetOrder->valid = true;
            $targetOrder->save();
        }
    }

}
