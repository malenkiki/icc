<?php
/*
 * Copyright (c) 2013 Michel Petit <petit.michel@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

(@include_once __DIR__ . '/../vendor/autoload.php') || @include_once __DIR__ . '/../../../autoload.php';

class IccTest extends PHPUnit_Framework_TestCase
{
    public function testInstanciateWithSuccess()
    {
        $icc = new Malenki\Icc('tests/sRGB.icc');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInstanciateWillFailBecauseInvalidArgument()
    {
        $icc = new Malenki\Icc('');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInstanciateWillFailBecauseFileDoesNotExist()
    {
        $icc = new Malenki\Icc('sRGB.icc');
    }

    public function testHeader()
    {
        $icc = new Malenki\Icc('tests/sRGB.icc');
        $this->assertEquals(filesize('tests/sRGB.icc'), $icc->header->profileSize);
        $this->assertEquals('lcms', $icc->header->preferedCmmType);
        $this->assertEquals('2.3.0', $icc->header->profileVersion);
        $this->assertEquals('mntr', $icc->header->profileDeviceClass->signature);
        $this->assertEquals('RGB', $icc->header->colourSpace);
        $this->assertEquals('XYZ', $icc->header->pcs);
        $this->assertEquals('2004-08-13 12:18:06', $icc->header->dateTime->str);
        $this->assertEquals('acsp', $icc->header->profileFileSignature);
        $this->assertTrue($icc->header->primaryPlatformSignature->exists);
        $this->assertEquals('MSFT', $icc->header->primaryPlatformSignature->signature);
        //TODO profile flag
        $this->assertFalse($icc->header->deviceManufacturer->exists);
        $this->assertFalse($icc->header->deviceModel->exists);

        //var_dump($icc->tag->toc);
        //var_dump($icc->tag->data[0]);
        //var_dump($icc->tag->data[2]);
    }
}
