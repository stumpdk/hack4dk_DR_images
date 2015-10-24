<?php

require( __DIR__ . '/../../vendor/autoload.php');

class S3Helper
{
    //To get this to work: Put a credentials file in /home/your_user/.aws/
    private $sharedConfig = [
            'region'  => 'eu-west-1',
            'version' => 'latest'
        ];
    
    public function getFileContents($id)
    {
        $sdk = new Aws\Sdk($this->sharedConfig);
        $s3Client = $sdk->createS3();
        
        try {
            $result = $s3Client->getObject([
                    'Bucket' => 'crowdsourcing-dr-images',
                    'Key'    => $id
                ]);
        }
        catch (Exception $e) {
            return false;
        }        
        
        return $result;
    }
    
    public function put($id, $fileContent)
    {
        $sdk = new Aws\Sdk($this->sharedConfig);
        $s3Client = $sdk->createS3();
        
        try {
            $result = $s3Client->putObject([
                    'Bucket' => 'crowdsourcing-dr-images',
                    'Key'    => $id,
                    'Body'   => $fileContent,
                    'ContentType'  => 'image/jpeg',
                    'StorageClass' => 'REDUCED_REDUNDANCY',
                ]);
        }
        catch (Exception $e) {
           // var_dump($e);
            return false;
        }        
        
        return true;
    }
}