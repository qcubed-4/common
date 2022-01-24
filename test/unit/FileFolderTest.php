<?php

use QCubed\File;
use QCubed\Folder;

/**
 *
 * @package Tests
 */
class FileFolderTest extends \QCubed\Test\UnitTestCaseBase
{

    public function testCreateAndTeardown()
    {
        $tmpDir = sys_get_temp_dir();

        $folderName = $tmpDir . "/test";
        $fileName = $folderName . "/testFile.txt";
        $strTest = "here is a test";

        Folder::makeDirectory($folderName, 0777);

        File::writeFile($fileName, $strTest);

        $strTest2 = File::readFile($fileName);

        $this->assertEquals($strTest, $strTest2);

        $this->assertEquals(1, Folder::countItems($folderName));

        Folder::emptyContents($folderName);

        $this->assertEquals(0,  Folder::countItems($folderName));

        //Folder::deleteFolder($folderName);
    }

}