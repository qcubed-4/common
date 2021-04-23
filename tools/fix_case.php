#!/usr/bin/env php
<?php
/**
 * MIT License
 *
 * Copyright (c) Shannon Pekary spekary@gmail.com
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * PHP script to convert cases of function definitions and function calls to camelCase.
 * It also converts case of const declarations to UPPER_CASE
 */

if (count($argv) < 2) {
    echo 'Usage: fix_case [-R] outFile(s)';
}

$options = getopt('R');
$blnRecursive = isset($options['R']);

/**
 * Change constant name to UPPER_CASE format. It might already be there, so we should be careful.
 * @param $strConst
 */
function UCase($strConst)
{
    // If it is already upper case, then do nothing.
    if (strtoupper($strConst) === $strConst) {
        return $strConst;
    }
    return strtoupper(preg_replace('/(?<!^)[A-Z]/', '_$0', $strConst));
}
function processFile($file) {
    if ($file == '.' || $file == '..') {
        return;
    }

    $strFile = file_get_contents($file);

// static function calls
    $newFile = preg_replace_callback(
        '/::([A-Z])(\\w*)\\s*\\(/',
        function ($matches) {
            return '::' . strtolower($matches[1]) . $matches[2] . '(';
        }, $strFile);

// dynamic methods
    $newFile = preg_replace_callback(
        '/->([A-Z])(\\w*)\\s*\\(/',
        function ($matches) {
            return '->' . strtolower($matches[1]) . $matches[2] . '(';
        }, $newFile);

// function declarations
    $newFile = preg_replace_callback(
        '/function\\s+([A-Z])(\\w*)\\s*\\(/',
        function ($matches) {
            return 'function ' . strtolower($matches[1]) . $matches[2] . '(';
        }, $newFile);

// const declarations
    $a = [];
    $newFile = preg_replace_callback(
        '/^(\\s*)const\\s+(\\w*)\\s*=/m',
        function ($matches) use (&$a) {
            // record a change we made
            $a[$matches[2]] = UCase($matches[2]);
            return $matches[1] .    // beginning spaces don't change
                'const ' .
                UCase($matches[2]) .
                ' =';
        }, $newFile);

// Fix up all occurances of its own constants internally
    foreach ($a as $pattern => $rep) {
        $newFile = preg_replace(
            '/::' . $pattern . '/',
            '::' . $rep, $newFile);
    }

    file_put_contents($file, $newFile);

}

function processFiles($files) {
    global $blnRecursive;

    foreach ($files as $file) {
        if (is_dir($file)) {
            if ($file != '.' &&
                $file != '..' &&
                $blnRecursive)
            {
                $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file));
                $filter = new RegexIterator($objects, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
                foreach ($filter as $name=>$object) {
                    processFile($name);
                }
            }
        } else {
            processFile($file);
        }
    }
}

$files = $argv;
array_shift($files);
for($i = 0; $i < count($options); $i++) {
    array_shift($files);
}

processFiles($files);
