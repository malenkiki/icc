<?php
/*
Copyright (c) 2014 Michel Petit <petit.michel@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */


namespace Malenki;

/**
 * Handle ICC file to deal with color profile.
 * 
 * @author Michel Petit <petit.michel@gmail.com> 
 * @license MIT
 */
class Icc
{
    protected $fp = null;



    public function __construct($str_file)
    {
        $this->fp = fopen($str_file, 'rb');

        $this->extractProfileSize();
        $this->extractPreferedCmmType();
        $this->extractProfileVersion();
        $this->extractProfileDeviceClass();
        $this->extractColourSpaceOfData();
        $this->extractPcs();
        $this->extractDateTime();
    }


    public function __destruct()
    {
        fclose($this->fp);
    }


    private function extractProfileSize()
    {
        $arr = unpack("C4", fread($fp, 4));
        // Workaround
        foreach($arr as $k => $b)
        {
            $arr[$k] = dechex($b);
        }

        return hexdec(implode('', $arr));
    }

    private function extractPreferedCmmType()
    {
        $arr = unpack("A4", fread($fp, 4));
        return array_pop($arr);
    }


    private function extractProfileVersion()
    {
        $arr_out = array();

        $arr = unpack("C4", fread($fp, 4));
        $bin = str_pad(decbin($arr[2]), 8, '0', STR_PAD_LEFT);
        $arr_out[] = $arr[1];
        $arr_out[] = bindec(substr($bin, 0, 4)); //minor version
        $arr_out[] = bindec(substr($bin, 4, 4)); //bug fix version

        return implode('.', $arr_out);
    }


    private function extractProfileDeviceClass()
    {
        $arr = unpack("A4", fread($fp, 4));
        return array_pop($arr);
    }


    private function extractColourSpaceOfData()
    {
        $arr = unpack("A4", fread($fp, 4));
        return array_pop($arr);
    }


    private function extractPcs()
    {
        $arr = unpack("A4", fread($fp, 4));
        return array_pop($arr);
    }


    // TODO
    private function extractDateTime()
    {
        $arr = unpack("A12", fread($fp, 12));
        var_dump(array_pop($arr));
    }
}
