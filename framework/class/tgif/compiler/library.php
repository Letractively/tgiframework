<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Holder of {@link tgif_compiler_library}
 *
 * @package tgiframework
 * @subpackage ui
 * @copyright c.2010 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_compiler_library
/**
 * Interface for an external compiler library.
 *
 * Implement these static methods to extend the functionality of a compiler.
 *
 * @package tgiframework
 * @subpackage ui
 * @author terry chay <tychay@php.net>
 */
interface tgif_compiler_library
{
    // UTILITY METHOD
    // {{{ - _bind($configs)
    /**
     * @param array $config the configuration used in constructor
     */
    private function _bind($configs) {
        $this->_options = $configs;
    }
    // }}}
    // SIGNATURE METHODS:
    // {{{ - generateSignature($fileName)
    /**
     * Figure a way of making a signature unique
     *
     * @param string $fileName the name of the library file
     * @return string the signature
     */
    public function generateSignature($fileName);
    // }}}
    // {{{ - generateFileData($fileName)
    /**
     * Turn a file name into file data
     *
     * @param string $fileName the name of the library file
     * @return array The library file's filedata
     */
    public function generateFileData($fileName);
    // }}}
    // {{{ - compileFile($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * Turn a file name into file data
     *
     * @param array $sourceFileData The file data of the resource. This will
     * be modified to the target file data if successful.
     * @param string $targetFileName The file name of the destination file
     * @param string $targetFilePath The path to a physically unique file to
     * place the destination file.
     * @param tgif_compiler $compilerObj for introspection as needed. For
     * instance it may be useful to call {@link
     * tgif_compiler::compileFileInternal() compileFileInternal()} to further
     * compress a file.
     * @return boolean success or failure
     */
    public function compileFile(&$sourceFileData, $targetFileName, $targetFilePath, $compilerObj);
    // }}}
    // {{{ - compileFileService($sourceFileData,$targetFileName,$targetFilePath,$compilerObj)
    /**
     * Turn a file name into file data via a "service" (delayed call).
     *
     * Note that if service is on, then even if the result is instanteous,
     * it is always assumed to have "failed".
     *
     * @param array $sourceFileData The file data of the resource. This will
     * be modified to the target file data if successful.
     * @param string $targetFileName The file name of the destination file
     * @param string $targetFilePath The path to a physically unique file to
     * place the destination file.
     * @param tgif_compiler $compilerObj for introspection as needed
     * @return boolean success or failure
     */
    public function compileFileService($targetFileName, $targetFilePath, $compilerObj);
    // }}}
    // {{{ - catFiles($fileDatas)
    /**
     * Allow you to catenate files at the front (or in place).
     *
     * @param array $fileDatas a list of file data to catenate together. You
     * can manipulate this result set however you want. But be warned, if you
     * do no purge all library instances, this will get ugly.
     * @return array a list of file data that is separate from regular file
     * catenation.
     */
    public function catFiles($fileName);
    // }}}
    // {{{ - generateUrl($fileData)
    /**
     * Turn a file data into a full URL.
     *
     * Note if the resource is really a local file. then it is suggested you
     * modify {@link cat_files()} to remove the 'library' property for these
     * files and let the automated routine handle it.
     *
     * @param string $fileName the name of the library file
     * @return string the url
     */
    public function generateUrl($fileName);
    // }}}
}
// }}}
?>
