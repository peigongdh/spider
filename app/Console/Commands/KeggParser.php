<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PHPHtmlParser\Dom;

class KeggParser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kegg-parser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'kegg-parser';

    protected $fileOutput = "kegg-out";

    protected $fileParseOutput = "kegg-parse-out";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        ini_set("memory_limit", "4G");
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fileName = "R10-11_KO";
        $fileOutputPath = $this->fileOutput . "/" . $fileName . ".txt";

        // debug get id
        // $id = $this->getUploadFileId($fileOutputPath);
        // $lis = $this->getRows($fileName);

        $rows = $this->getRows($fileOutputPath);
        $rowsStr = implode("\n", $rows);

        $fileParseOutputPath = $this->fileParseOutput . "/" . $fileName . ".csv";
        file_put_contents($fileParseOutputPath, $rowsStr);
        return 0;
    }

    public function getUploadFileId($fileName)
    {
        $content = file_get_contents($fileName);

        $dom = new Dom();
        $dom = $dom->loadStr($content);

        $id = "";
        foreach ($dom->find('input') as $v) {
            if ($v->name == "uploadfile") {
                $id = $v->value;
                break;
            }
        }
        return str_replace("/mapper.args", "", $id);
    }

    public function getRows($fileName)
    {
        $content = file_get_contents($fileName);

        $dom = new Dom();
        $dom = $dom->loadStr($content);

        $lis = [];
        foreach ($dom->find('li') as $li) {
            if ($li->text == "") {
                continue;
            }
            $liText = str_replace("&nbsp", " ", $li->text);

            $dtText = [];
            foreach ($li->find('a') as $dt) {
                if ($dt->text == "") {
                    continue;
                }
                // note: dt start with K?
                if ($dt->text[0] != 'K') {
                    continue;
                }
                $dtText[] = str_replace("&nbsp", " ", $dt->text);
            }

            $dtIndex = 0;
            foreach ($li->find('dd') as $dd) {
                if ($dd->text == "") {
                    continue;
                }
                $ddText = str_replace("&nbsp", " ", $dd->text);
                $liArray = [
                    $liText,
                    $dtText[$dtIndex++] ?: "",
                    $ddText
                ];

                // implode
                $lis[] = implode(";", $liArray);
            }
        }

        unset($content);

        return $lis;
    }
}
