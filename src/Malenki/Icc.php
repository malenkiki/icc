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


    protected static $arr_tag_signature = array(
        'A2B0' => 'AToB0Tag',
        'A2B1' => 'AToB1Tag',
        'A2B2' => 'AToB2Tag',
        'bXYZ' => 'blueMatrixColumnTag',
        'bTRC' => 'blueTRCTag',
        'B2A0' => 'BToA0Tag',
        'B2A1' => 'BToA1Tag',
        'B2A2' => 'BToA2Tag',
        'B2D0' => 'BToD0Tag',
        'B2D1' => 'BToD1Tag',
        'B2D2' => 'BToD2Tag',
        'B2D3' => 'BToD3Tag',
        'calt' => 'calibrationDateTimeTag',
        'targ' => 'charTargetTag',
        'chad' => 'chromaticAdaptationTag',
        'chrm' => 'chromaticityTag',
        'clro' => 'colorantOrderTag',
        'clrt' => 'colorantTableTag',
        'clot' => 'colorantTableOutTag',
        'ciis' => 'colorimetricIntentImageStateTag',
        'cprt' => 'copyrightTag',
        'dmnd' => 'deviceMfgDescTag',
        'dmdd' => 'deviceModelDescTag',
        'D2B0' => 'DToB0Tag',
        'D2B1' => 'DToB1Tag',
        'D2B2' => 'DToB2Tag',
        'D2B3' => 'DToB3Tag',
        'gamt' => 'gamutTag',
        'kTRC' => 'grayTRCTag',
        'gXYZ' => 'greenMatrixColumnTag',
        'gTRC' => 'greenTRCTag',
        'lumi' => 'luminanceTag',
        'meas' => 'measurementTag',
        'wtpt' => 'mediaWhitePointTag',
        'ncl2' => 'namedColor2Tag',
        'resp' => 'outputResponseTag',
        'rig0' => 'perceptualRenderingIntentGamutTag',
        'pre0' => 'preview0Tag',
        'pre1' => 'preview1Tag',
        'pre2' => 'preview2Tag',
        'desc' => 'profileDescriptionTag',
        'pseq' => 'profileSequenceDescTag',
        'psid' => 'profileSequenceIdentifierTag',
        'rXYZ' => 'redMatrixColumnTag',
        'rTRC' => 'redTRCTag',
        'rig2' => 'saturationRenderingIntentGamutTag',
        'tech' => 'technologyTag',
        'vued' => 'viewingCondDescTag',
        'view' => 'viewingConditionsTag'
    );

    protected $fp = null;

    protected $header = null;

    public function __get($name)
    {
        if(in_array($name, array('header', 'tag')))
        {
            return $this->$name;
        }
    }


    protected static function s15Fixed16Number($mix)
    {
        if(is_array($mix))
        {
            $whole_part = bindec(decbin($mix[0]).decbin($mix[1]));
            $fractionary_part = bindec(decbin($mix[2]).decbin($mix[3])) / 0x10000;

            return $whole_part + $fractionary_part;
        } 
    }


    public function __construct($str_file)
    {
        if(!is_string($str_file) || strlen(trim($str_file)) == 0)
        {
            throw new \InvalidArgumentException('File name must be a valid string');
        }

        if(!file_exists($str_file))
        {
            throw new \RuntimeException(sprintf('The file "%s" does not exist.', $str_file));
        }

        if(!is_readable($str_file))
        {
            throw new \RuntimeException(sprintf('The file "%s" does not exist.', $str_file));
        }

        $this->fp = fopen($str_file, 'rb');

        $this->header = new \stdClass();
        $this->tag = new \stdClass();

        $this->header->profileSize = $this->extractProfileSize();
        $this->header->preferedCmmType = $this->extractPreferedCmmType();
        $this->header->profileVersion = $this->extractProfileVersion();
        $this->header->profileDeviceClass = $this->extractProfileDeviceClass();
        $this->header->colourSpace = $this->extractColourSpaceOfData();
        $this->header->pcs = $this->extractPcs();
        $this->header->dateTime = $this->extractDateTime();
        $this->header->profileFileSignature = $this->extractProfileFileSignature(); // should be 'acsp', if not error -> todo: do test for that
        $this->header->primaryPlatformSignature = $this->extractPrimaryPlatformSignature();
        $this->header->profileFlags = $this->extractProfileFlags();
        $this->header->deviceManufacturer = $this->extractManufacturer();
        $this->header->deviceModel = $this->extractModel();
        $this->header->deviceAttributes = $this->extractAttributes();
        $this->header->renderingIntent = $this->extractRenderingIntent();
        $this->header->pcsIlluminant = $this->extractPcsIlluminant();
        $this->header->profileCreator = $this->extractCreator();
        $this->header->profileId = $this->extractProfileId();
        $this->header->reservedBytes = $this->extractReservedBytes();
        
        $this->tag->count = $this->extractTagCount();
        $this->tag->toc = array();
        $this->tag->data = array();

        for($i = 0; $i < $this->tag->count; $i++)
        {
            $tag = new \stdClass();
            $tag->signature = $this->extractTagSignature();
            $tag->offset = $this->extractTagOffset();
            $tag->size = $this->extractTagSize();
            $this->tag->toc[] = $tag;
        }

        // TODO how to deal with offset == 0 ? (external CMM case)
        if($this->tag->toc[0]->offset > 0)
        {
            foreach($this->tag->toc as $idx)
            {
                $this->tag->data[] = $this->extractTag($idx);
            }
        }
    }


    public function __destruct()
    {
        fclose($this->fp);
    }


    private function extractProfileSize()
    {
        return array_sum(unpack("N*", fread($this->fp, 4)));
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
        $arr = unpack("n*", fread($this->fp, 12));


        $date = new \stdClass();
        /*
        $date->year = hexdec(dechex($arr[1]).dechex($arr[2]));
        $date->month = hexdec(dechex($arr[3]).dechex($arr[4]));
        $date->day = hexdec(dechex($arr[5]).dechex($arr[6]));
        $date->hours = hexdec(dechex($arr[7]).dechex($arr[8]));
        $date->minutes = hexdec(dechex($arr[9]).dechex($arr[10]));
        $date->seconds = hexdec(dechex($arr[11]).dechex($arr[12]));
         */
        $date->year = $arr[1];
        $date->month = $arr[2];
        $date->day = $arr[3];
        $date->hours = $arr[4];
        $date->minutes = $arr[5];
        $date->seconds = $arr[6];
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
    
    //TODO get the string, find example ICC file with that
    private function extractManufacturer()
    {
        $out = new \stdClass();

        $bin = fread($this->fp, 4);
        $arr = unpack("A4", $bin);

        if(array_sum($arr) == 0)
        {
            $out->exists = false;
        }
        else
        {
            $out->exists = true;
            $out->signature = unpack("A4", $bin);
        }

        return $out;
    }
    
    //TODO get the string, find example ICC file with that
    private function extractModel()
    {
        $out = new \stdClass();

        $bin = fread($this->fp, 4);
        $arr = unpack("A4", $bin);

        if(array_sum($arr) == 0)
        {
            $out->exists = false;
        }
        else
        {
            $out->exists = true;
            $out->signature = unpack("A4", $bin);
        }

        return $out;
    }
    
    
    
    // TODO
    private function extractAttributes()
    {
        $out = new \stdClass();

        $bin = fread($this->fp, 8);
        $arr = unpack("C8", $bin);

        foreach($arr as $k => $v)
        {
            $arr[$k] = decbin($v);
        }

        $out->raw = implode('', $arr);
        /*
        if(array_sum($arr) == 0)
        {
            $out->exists = false;
        }
        else
        {
            $out->exists = true;
            $out->signature = unpack("A4", $bin);
        }*/

        return $out;
    }


    // TODO
    private function extractRenderingIntent()
    {
        $arr = unpack("C4", fread($this->fp, 4));
        return $arr;
    }
    
    // TODO
    private function extractPcsIlluminant()
    {
        $arr = unpack("n*", fread($this->fp, 12));
        
        $out = new \stdClass();
        $out->x = $arr[1] + $arr[2] / 0x10000;
        $out->y = $arr[3] + $arr[4] / 0x10000;
        $out->z = $arr[5] + $arr[6] / 0x10000;

        /*
        $arr = array_chunk(unpack("C12", fread($this->fp, 12)), 4);
        $out = new \stdClass();
        $out->x = self::s15Fixed16Number($arr[0]);
        $out->y = self::s15Fixed16Number($arr[1]);
        $out->z = self::s15Fixed16Number($arr[2]);
         */
        return $out;
    }
    

    //TODO get the string, find example ICC file with that
    private function extractCreator()
    {
        $out = new \stdClass();

        $bin = fread($this->fp, 4);
        $arr = unpack("A4", $bin);

        if(array_sum($arr) == 0)
        {
            $out->exists = false;
        }
        else
        {
            $out->exists = true;
            $out->signature = unpack("A4", $bin);
        }

        return $out;
    }
    
    // TODO
    private function extractProfileId()
    {
        return array_pop(unpack("h*", fread($this->fp, 16)));
    }
    
    
    // TODO
    private function extractReservedBytes()
    {
        return array_pop(unpack("C28", fread($this->fp, 28)));
    }
    
    private function extractTagCount()
    {
        return array_sum(unpack("C4", fread($this->fp, 4)));
    }
    
    private function extractTagSignature()
    {
        return array_pop(unpack("A4", fread($this->fp, 4)));
    }
    
    private function extractTagOffset()
    {
        return array_pop(unpack("N*", fread($this->fp, 4)));
    }
    
    
    private function extractTagSize()
    {
        return array_pop(unpack("N*", fread($this->fp, 4)));
    }
    

    private function extractTag($tag)
    {
        $out = new \stdClass();
        
        fseek($this->fp, $tag->offset);
        
        $out->boundary = array_pop(unpack('A4', fread($this->fp, 4)));
            
        $out->raw = fread($this->fp, $tag->size - 4);
        return $out;
    }
    
}
