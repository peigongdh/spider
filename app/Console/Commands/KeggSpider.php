<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PHPHtmlParser\Dom;

class KeggSpider extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kegg-spider';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'kegg-spider';

    protected $keggApi = "https://www.kegg.jp/kegg-bin/find_pathway_object";

    protected $filePath = "kegg";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
//        $fileName = "R10-11_KO.txt";
//        $filePath = $this->filePath . "/" . $fileName;
//        $fileStr = file_get_contents($filePath);
////        $request = Http::withHeaders([
////            'Sec-Fetch-Site' => 'same-origin',
////            'Sec-Fetch-Mod' => 'navigate',
////            'Sec-Fetch-User' => '?1',
////            'Sec-Fetch-Dest' => 'document',
////            'Upgrade-Insecure-Requests' => '1',
////            'Host' => 'www.kegg.jp',
////        ])->attach(
////            'attachment', $fileStr, $fileName
////        )->timeout(300);
//        $request = Http::withHeaders([
//            'Sec-Fetch-Site' => 'same-origin',
//            'Sec-Fetch-Mod' => 'navigate',
//            'Sec-Fetch-User' => '?1',
//            'Sec-Fetch-Dest' => 'document',
//            'Upgrade-Insecure-Requests' => '1',
//            'Host' => 'www.kegg.jp',
//        ])->timeout(300);
//        $request->withBody(base64_encode($fileStr), "file");
//        $response = $request->post($this->keggApi);
//        echo $response->body();
//        return 0;


        $fileName = "R10-11_KO.html";
        $response = $this->getByCurl($fileName);
        $id = $this->getUploadFileId($response);
        echo $id;

//        $fileName = "R10-11_KO.html";
//        $id = $this->getUploadFileId(file_get_contents($this->filePath . "/" . $fileName));
        return 0;
    }

    public function getByCurl($fileName)
    {
        $filePath = $this->filePath . "/" . $fileName;
        $fileStr = file_get_contents($filePath);

        $curl = curl_init();

        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;

        $postData = $this->buildDataFiles($boundary, [], [$filePath => $fileStr]);


        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->keggApi,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                //"Authorization: Bearer $TOKEN",
                "Content-Type: multipart/form-data; boundary=" . $delimiter,
                "Content-Length: " . strlen($postData)

            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    function buildDataFiles($boundary, $fields, $files)
    {
        $data = '';
        $eol = "\r\n";

        $delimiter = '-------------' . $boundary;

        foreach ($fields as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
                . $content . $eol;
        }

        foreach ($files as $name => $content) {
            $data .= "--" . $delimiter . $eol
                . 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
                //. 'Content-Type: image/png'.$eol
                . 'Content-Transfer-Encoding: binary' . $eol;

            $data .= $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $delimiter . "--" . $eol;

        return $data;
    }

    function getUploadFileId($mapperResult)
    {
        $id = "";
        $dom = new Dom();
        $dom = $dom->loadStr($mapperResult);
        foreach ($dom->find('input') as $v) {
            if ($v->name == "uploadfile") {
                $id = $v->value;
                break;
            }
        }
        return str_replace("/mapper.args", "", $id);
    }
}
