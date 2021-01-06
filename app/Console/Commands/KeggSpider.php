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

    protected $fileInput = "kegg-in";

    protected $fileOutput = "kegg-out";

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


        $fileName = "R10-11_KO.txt";
        $response = $this->getByCurl($fileName);
        $fileOutputPath = $this->fileOutput . "/" . $fileName;
        file_put_contents($fileOutputPath, $response);
        echo strlen($response);
        echo "\n";
        $id = $this->getUploadFileId($response);
        echo $id;

//        $fileName = "R10-11_KO.html";
//        $id = $this->getUploadFileId(file_get_contents($this->filePath . "/" . $fileName));
        return 0;
    }

    public function getByCurl($fileName)
    {
        $filePath = $this->fileInput . "/" . $fileName;
        $fileStr = file_get_contents($filePath);

        $curl = curl_init();

        $boundary = uniqid();
        $delimiter = '-------------' . $boundary;

        $postData = $this->buildDataFiles($boundary, [], [$fileName => $fileStr]);


        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->keggApi,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 300,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                //"Authorization: Bearer $TOKEN",
                "Content-Type: multipart/form-data; boundary=" . $delimiter,
                "Content-Length: " . strlen($postData),
                // copy from curl
                'Host: www.kegg.jp',
                'Connection: keep-alive',
                // 'Content-Length: 3607932',
                'Cache-Control: max-age=0',
                'sec-ch-ua: "Google Chrome";v="87", " Not;A Brand";v="99", "Chromium";v="87"',
                'sec-ch-ua-mobile: ?0',
                'Origin: https://www.kegg.jp',
                'Upgrade-Insecure-Requests: 1',
                'DNT: 1',
                // 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundaryQ9icRMqU9iSBtMqE',
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.88 Safari/537.36',
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-Mode: navigate',
                'Sec-Fetch-User: ?1',
                'Sec-Fetch-Dest: document',
                'Referer: https://www.kegg.jp/kegg/tool/map_pathway.html',
                'Accept-Encoding: gzip, deflate, br',
                'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8',
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
