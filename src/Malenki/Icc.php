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
    protected static $arr_primary_platforms = array(
        'APPL' => 'Apple Computer, Inc.',
        'MSFT' => 'Microsoft Corporation',
        'SGI'  => 'Silicon Graphics, Inc.',
        'SUNW' => 'Sun Microsystems, Inc.'
    );

    protected static $arr_profile_classes = array(
        'scnr' => 'Input device profile',
        'mntr' => 'Display device profile',
        'prtr' => 'Output device profile',
        'link' => 'DeviceLink profile',
        'spac' => 'ColorSpace profile',
        'abst' => 'Abstract profile',
        'nmcl' => 'NamedColor profile'
    );

    protected $fp = null;

    protected $header = null;

    public function __get($name)
    {
        if($name == 'header')
        {
            return $this->$name;
        }
    }



    public function __construct($str_file)
    {
        $this->fp = fopen($str_file, 'rb');

        $this->header = new \stdClass();

        $this->header->profileSize = $this->extractProfileSize();
        $this->header->preferedCmmType = $this->extractPreferedCmmType();
        $this->header->profileVersion = $this->extractProfileVersion();
        $this->header->profileDeviceClass = $this->extractProfileDeviceClass();
        $this->header->colourSpace = $this->extractColourSpaceOfData();
        $this->header->pcs = $this->extractPcs();
        $this->header->DateTime = $this->extractDateTime();
        $this->header->profileFileSignature = $this->extractProfileFileSignature(); // should be 'acsp', if not error -> todo: do test for that
        $this->header->primaryPlatformSignature = $this->extractPrimaryPlatformSignature();
        $this->header->profileFlags = $this->extractProfileFlags();
        $this->header->manufacturer = $this->extractManufacturer();
    }


    public function __destruct()
    {
        fclose($this->fp);
    }


    private function extractProfileSize()
    {
        $arr = unpack("C4", fread($this->fp, 4));
        // Workaround
        foreach($arr as $k => $b)
        {
            $arr[$k] = dechex($b);
        }

        return hexdec(implode('', $arr));
    }

    private function extractPreferedCmmType()
    {
        $arr = unpack("A4", fread($this->fp, 4));
        return array_pop($arr);
    }


    private function extractProfileVersion()
    {
        $arr_out = array();

        $arr = unpack("C4", fread($this->fp, 4));
        $bin = str_pad(decbin($arr[2]), 8, '0', STR_PAD_LEFT);
        $arr_out[] = $arr[1];
        $arr_out[] = bindec(substr($bin, 0, 4)); //minor version
        $arr_out[] = bindec(substr($bin, 4, 4)); //bug fix version

        return implode('.', $arr_out);
    }


    private function extractProfileDeviceClass()
    {
        $out = new \stdClass();
        $arr = unpack("A4", fread($this->fp, 4));
        $out->signature = array_pop($arr);
        $out->name = self::$arr_profile_classes[$out->signature];

        return $out;
    }


    // TODO add name too
    private function extractColourSpaceOfData()
    {
        $arr = unpack("A4", fread($this->fp, 4));
        return array_pop($arr);
    }


    private function extractPcs()
    {
        $arr = unpack("A4", fread($this->fp, 4));
        return array_pop($arr);
    }


    private function extractDateTime()
    {
        $arr = unpack("C12", fread($this->fp, 12));

        $date = new \stdClass();
        $date->year = hexdec(dechex($arr[1]).dechex($arr[2]));
        $date->month = hexdec(dechex($arr[3]).dechex($arr[4]));
        $date->day = hexdec(dechex($arr[5]).dechex($arr[6]));
        $date->hours = hexdec(dechex($arr[7]).dechex($arr[8]));
        $date->minutes = hexdec(dechex($arr[9]).dechex($arr[10]));
        $date->seconds = hexdec(dechex($arr[11]).dechex($arr[12]));
        $date->str = sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            $date->year,
            $date->month,
            $date->day,
            $date->hours,
            $date->minutes,
            $date->seconds
        );

        return $date;
    }



    private function extractProfileFileSignature()
    {
        $arr = unpack("A4", fread($this->fp, 4));
        return array_pop($arr);
    }



    private function extractPrimaryPlatformSignature()
    {
        $out = new \stdClass();
        $arr = unpack("A4", fread($this->fp, 4));
        $out->signature = array_pop($arr);

        if(array_key_exists($out->signature, self::$arr_primary_platforms))
        {
            $out->exists = true;
            $out->name = self::$arr_primary_platforms[$out->signature];
        }
        else
        {
            $out->exists = false;
            $out->name = null;
        }

        return $out;
    }
    
    
    // TODO
    private function extractProfileFlags()
    {
        $arr = unpack("C4", fread($this->fp, 4));
        return $arr;
    }
    
    private function extractManufacturer()
    {
        $arr = unpack("C4", fread($this->fp, 4));
        return $arr;
    }
}
