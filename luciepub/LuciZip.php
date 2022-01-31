<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * Modified
 * 2012-12-04 Mikael Ylikoski
 *   - Changed sendZip to allow Content-Disposition: inline
 *   - Changed sendZip to allow UTF-8 encoding for filename
 *
 * 2012-12-14 Mikael Ylikoski
 *   - Use htmlentities() in error message
 *
 * 2014-01-10 Mikael Ylikoski
 *   - Renamed class to LuciZip
 *
 * version 1.50
 */


/**
 * LuciZip class and functions - Class to create and manage a Zip file.
 *
 * Initially inspired by Createzipfile by Rochak Chauhan  www.rochakchauhan.com (http://www.phpclasses.org/browse/package/2322.html)
 * and
 * http://www.pkware.com/documents/casestudies/APPNOTE.TXT Zip file specification.
 *
 * License: GNU LGPL, Attribution required for commercial implementations, requested for everything else.
 *
 * @author A. Grandt <php@grandt.com>
 * @package   local_wikiexport
 * @copyright 2009-2013 A. Grandt
 * @license GNU LGPL 2.1
 * @link http://www.phpclasses.org/package/6110
 * @link https://github.com/Grandt/PHPZip
 */
class LuciZip {
    /**
     * VERSION constant.
     */
    const VERSION = 1.50;
    // Local file header signature.
    /**
     * ZIP_LOCAL_FILE_HEADER constant.
     */
    const ZIP_LOCAL_FILE_HEADER = "\x50\x4b\x03\x04";
    // Central file header signature.
    /**
     * ZIP_CENTRAL_FILE_HEADER constant.
     */
    const ZIP_CENTRAL_FILE_HEADER = "\x50\x4b\x01\x02";
    // End of Central directory record.
    /**
     * ZIP_END_OF_CENTRAL_DIRECTORY constant.
     */
    const ZIP_END_OF_CENTRAL_DIRECTORY = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    // Permission 755 drwxr-xr-x = (((S_IFDIR | 0755) << 16) | S_DOS_D).
    /**
     * EXT_FILE_ATTR_DIR constant.
     */
    const EXT_FILE_ATTR_DIR = 010173200020;
    // Permission 644 -rw-r--r-- = (((S_IFREG | 0644) << 16) | S_DOS_A).
    /**
     * EXT_FILE_ATTR_FILE constant.
     */
    const EXT_FILE_ATTR_FILE = 020151000040;
    // Version needed to extract.
    /**
     * ATTR_VERSION_TO_EXTRACT constant.
     */
    const ATTR_VERSION_TO_EXTRACT = "\x14\x00";
    // Made By Version.
    /**
     * ATTR_MADE_BY_VERSION constant.
     */
    const ATTR_MADE_BY_VERSION = "\x1E\x03";

    // Unix file types.
    // Named pipe (fifo).
    /**
     * S_IFIFO constant.
     */
    const S_IFIFO  = 0010000;
    // Character special.
    /**
     * S_IFCHR constant.
     */
    const S_IFCHR  = 0020000;
    // Directory.
    /**
     * S_IFDIR constant.
     */
    const S_IFDIR  = 0040000;
    // Block special.
    /**
     * S_IFBLK constant.
     */
    const S_IFBLK  = 0060000;
    // Regular.
    /**
     * S_IFREG constant.
     */
    const S_IFREG  = 0100000;
    // Symbolic link.
    /**
     * S_IFLNK constant.
     */
    const S_IFLNK  = 0120000;
    // Socket.
    /**
     * S_IFSOCK constant.
     */
    const S_IFSOCK = 0140000;

    // Bits setuid/setgid/sticky bits, the same as for chmod.
    // Set user id on execution.
    /**
     * S_ISUID constant.
     */
    const S_ISUID  = 0004000;
    // Set group id on execution.
    /**
     * S_ISGID constant.
     */
    const S_ISGID  = 0002000;
    // Sticky bit.
    /**
     * S_ISTXT constant.
     */
    const S_ISTXT  = 0001000;

    // And of course, the other 12 bits are for the permissions, the same as for chmod:
    // when addding these up, you can also just write the permissions as a simgle octal number
    // ie. 0755. The leading 0 specifies octal notation.
    // RWX mask for owner.
    /**
     * S_IRWXU constant.
     */
    const S_IRWXU  = 0000700;
    // R for owner.
    /**
     * S_IRUSR constant.
     */
    const S_IRUSR  = 0000400;
    // W for owner.
    /**
     * S_IWUSR constant.
     */
    const S_IWUSR  = 0000200;
    // X for owner.
    /**
     * S_IXUSR constant.
     */
    const S_IXUSR  = 0000100;
    // RWX mask for group.
    /**
     * S_IRWXG constant.
     */
    const S_IRWXG  = 0000070;
    // R for group.
    /**
     * S_IRGRP constant.
     */
    const S_IRGRP  = 0000040;
    // W for group.
    /**
     * S_IWGRP constant.
     */
    const S_IWGRP  = 0000020;
    // X for group.
    /**
     *
     */
    const S_IXGRP  = 0000010;
    // RWX mask for other.
    /**
     * S_IRWXO constant.
     */
    const S_IRWXO  = 0000007;
    // R for other.
    /**
     * S_IROTH constant.
     */
    const S_IROTH  = 0000004;
    // W for other.
    /**
     * S_IWOTH constant.
     */
    const S_IWOTH  = 0000002;
    // X for other.
    /**
     * S_IXOTH constant.
     */
    const S_IXOTH  = 0000001;
    // Save swapped text even after use.
    /**
     * S_ISVTX constant.
     */
    const S_ISVTX  = 0001000;

    // Filetype, sticky and permissions are added up, and shifted 16 bits left BEFORE adding the DOS flags.
    // DOS file type flags, we really only use the S_DOS_D flag.
    // DOS flag for Archive.
    /**
     * S_DOS_A constant.
     */
    const S_DOS_A  = 0000040;
    // DOS flag for Directory.
    /**
     * S_DOS_D constant.
     */
    const S_DOS_D  = 0000020;
    // DOS flag for Volume.
    /**
     * S_DOS_V constant.
     */
    const S_DOS_V  = 0000010;
    // DOS flag for System.
    /**
     * S_DOS_S constant.
     */
    const S_DOS_S  = 0000004;
    // DOS flag for Hidden.
    /**
     * S_DOS_H constant.
     */
    const S_DOS_H  = 0000002;
    // DOS flag for Read Only.
    /**
     * S_DOS_R constant.
     */
    const S_DOS_R  = 0000001;

    // Autocreate tempfile if the zip data exceeds 1048576 bytes (1 MB).
    /**
     * @var int
     */
    private $zipmemorythreshold = 1048576;

    /**
     * @var string|null
     */
    private $zipdata = null;
    /**
     * @var false|resource|null
     */
    private $zipfile = null;
    /**
     * @var null
     */
    private $zipcomment = null;
    /**
     * Central directory.
     *
     * @var array
     */
    private $cdrec = array();
    /**
     * @var int
     */
    private $offset = 0;
    /**
     * @var bool
     */
    private $isfinalized = false;
    /**
     * @var bool
     */
    private $addextrafield = true;

    /**
     * @var int
     */
    private $streamchunksize = 65536;
    /**
     * @var null
     */
    private $streamfilepath = null;
    /**
     * @var null
     */
    private $streamtimestamp = null;
    /**
     * @var null
     */
    private $streamfilecomment = null;
    /**
     * @var null
     */
    private $streamfile = null;
    /**
     * @var null
     */
    private $streamdata = null;
    /**
     * @var int
     */
    private $streamfilelength = 0;
    /**
     * @var null
     */
    private $streamextfileattr = null;

    /**
     * Constructor method.
     * Write temp zip data to tempFile? Default FALSE.
     *
     * @param boolean $usezipfile
     */
    public function __construct($usezipfile = false) {
        if ($usezipfile) {
            $this->zipfile = tmpfile();
        } else {
            $this->zipdata = "";
        }
    }

    /**
     * Destructor method.
     */
    public function __destruct() {
        if (is_resource($this->zipfile)) {
            fclose($this->zipfile);
        }
        $this->zipdata = null;
    }

    /**
     * Extra fields on the Zip directory records are Unix time codes needed for compatibility on the default Mac zip archive tool.
     * These are enabled as default, as they do no harm elsewhere and only add 26 bytes per file added.
     *
     * TRUE (default) will enable adding of extra fields, anything else will disable it.
     *
     * @param bool $setextrafield
     */
    public function setextrafield($setextrafield = true) {
        $this->addextrafield = ($setextrafield === true);
    }

    /**
     * Set Zip archive comment.
     *
     * @param string $newcomment New comment. NULL to clear.
     *
     * @return bool $success
     */
    public function setcomment($newcomment = null) {
        if ($this->isfinalized) {
            return false;
        }
        $this->zipcomment = $newcomment;

        return true;
    }

    /**
     * Set zip file to write zip data to.
     * This will cause all present and future data written to this class to be written to this file.
     * This can be used at any time, even after the Zip Archive have been finalized. Any previous file will be closed.
     * Warning: If the given file already exists, it will be overwritten.
     *
     * @param string $filename
     *
     * @return bool $success
     */
    public function setzipfile($filename) {
        if (is_file($filename)) {
            unlink($filename);
        }
        $fd = fopen($filename, "x+b");
        if (is_resource($this->zipfile)) {
            rewind($this->zipfile);
            while (!feof($this->zipfile)) {
                fwrite($fd, fread($this->zipfile, $this->streamchunksize));
            }

            fclose($this->zipfile);
        } else {
            fwrite($fd, $this->zipdata);
            $this->zipdata = null;
        }
        $this->zipfile = $fd;

        return true;
    }

    /**
     * Add an empty directory entry to the zip archive.
     * Basically this is only used if an empty directory is added.
     *
     * @param string $directorypath Directory path and name to be added to the archive.
     * @param int    $timestamp     (Optional) Timestamp for the added directory,
     *                              if omitted or set to 0, the current time will be used.
     * @param string $filecomment   (Optional) Comment to be added to the archive for this directory.
     *                              To use filecomment, timestamp must be given.
     * @param int    $extfileattr   (Optional) The external file reference, use generateextattr() to generate this.
     *
     * @return bool $success
     */
    public function adddirectory($directorypath, $timestamp = 0, $filecomment = null, $extfileattr = self::EXT_FILE_ATTR_DIR) {
        if ($this->isfinalized) {
            return false;
        }
        $directorypath = str_replace("\\", "/", $directorypath);
        $directorypath = rtrim($directorypath, "/");

        if (strlen($directorypath) > 0) {
            $this->buildzipentry(
                $directorypath.'/',
                $filecomment, "\x00\x00",
                "\x00\x00", $timestamp,
                "\x00\x00\x00\x00",
                0,
                0,
                $extfileattr
            );

            return true;
        }
        return false;
    }

    /**
     * Add a file to the archive at the specified location and file name.
     *
     * @param string $data        File data.
     * @param string $filepath    filepath and name to be used in the archive.
     * @param int    $timestamp   (Optional) Timestamp for the added file,
     *                            if omitted or set to 0, the current time will be used.
     * @param string $filecomment (Optional) Comment to be added to the archive for this file.
     *                            To use filecomment, timestamp must be given.
     * @param bool   $compress    (Optional) Compress file, if set to FALSE the file will only be stored. Default TRUE.
     * @param int    $extfileattr (Optional) The external file reference, use generateextattr() to generate this.
     *
     * @return bool $success
     */
    public function addfile(
        $data,
        $filepath,
        $timestamp = 0,
        $filecomment = null,
        $compress = true,
        $extfileattr = self::EXT_FILE_ATTR_FILE
    ) {
        if ($this->isfinalized) {
            return false;
        }

        if (is_resource($data) && get_resource_type($data) == "stream") {
            $this->addlargefile($data, $filepath, $timestamp, $filecomment, $extfileattr);
            return false;
        }

        $gzdata = "";
        // Compression type 8 = deflate.
        $gztype = "\x08\x00";
        // General Purpose bit flags for compression type 8 it is: 0=Normal, 1=Maximum, 2=Fast, 3=super fast compression.
        $gpflags = "\x00\x00";
        $datalength = strlen($data);
        $filecrc32 = pack("V", crc32($data));

        if ($compress) {
            $gztmp = gzcompress($data);
            // NOTE: gzcompress adds a 2 byte header and 4 byte CRC we can't use.
            $gzdata = substr(substr($gztmp, 0, strlen($gztmp) - 4), 2);
            // The 2 byte header does contain useful data, though in this case the 2 parameters
            // we'd be interested in will always be 8 for compression type,
            // and 2 for General purpose flag.
            $gzlength = strlen($gzdata);
        } else {
            $gzlength = $datalength;
        }

        if ($gzlength >= $datalength) {
            $gzlength = $datalength;
            $gzdata = $data;
            // Compression type 0 = stored.
            $gztype = "\x00\x00";
            // Compression type 0 = stored.
            $gpflags = "\x00\x00";
        }

        if (!is_resource($this->zipfile) && ($this->offset + $gzlength) > $this->zipmemorythreshold) {
            $this->zipflush();
        }

        $this->buildzipentry(
            $filepath,
            $filecomment,
            $gpflags,
            $gztype,
            $timestamp,
            $filecrc32,
            $gzlength,
            $datalength,
            $extfileattr
        );

        $this->zipwrite($gzdata);

        return true;
    }

    /**
     * Adds the content to a directory.
     * @author Adam Schmalhofer <Adam.Schmalhofer@gmx.de>
     * @author A. Grandt
     *
     * @param string $realpath       Path on the file system.
     * @param string $zippath        filepath and name to be used in the archive.
     * @param bool $recursive        Add content recursively, default is TRUE.
     * @param bool $followsymlinks   Follow and add symbolic links, if they are accessible, default is TRUE.
     * @param array $addedfiles      Reference to the added files, this is used to prevent duplicates, efault is an empty array.
     *                               If you start the function by parsing an array, the array will be populated with the realpath
     *                               and zippath kay/value pairs added to the archive by the function.
     * @param bool $overridefilepermissions Force the use of the file/dir permissions set in the $extdirattr
     *                                 and $extfileattr parameters.
     * @param int $extdirattr        Permissions for directories.
     * @param int $extfileattr       Permissions for files.
     */
    public function adddirectoryontent(
        $realpath,
        $zippath,
        $recursive = true,
        $followsymlinks = true,
        &$addedfiles = array(),
        $overridefilepermissions = false,
        $extdirattr = self::EXT_FILE_ATTR_DIR,
        $extfileattr = self::EXT_FILE_ATTR_FILE
    ) {
        if (file_exists($realpath) && !isset($addedfiles[realpath($realpath)])) {
            if (is_dir($realpath)) {
                if ($overridefilepermissions) {
                             $this->adddirectory($zippath, 0, null, $extdirattr);
                } else {
                             $this->adddirectory($zippath, 0, null, self::getfileextattr($realpath));
                }
            }

            $addedfiles[realpath($realpath)] = $zippath;

            $iter = new DirectoryIterator($realpath);
            foreach ($iter as $file) {
                if ($file->isDot()) {
                    continue;
                }
                $newrealpath = $file->getPathname();
                $newzippath = self::pathjoin($zippath, $file->getfilename());

                if (file_exists($newrealpath) && ($followsymlinks === true || !is_link($newrealpath))) {
                    if ($file->isfile()) {
                        $addedfiles[realpath($newrealpath)] = $newzippath;
                        if ($overridefilepermissions) {
                                           $this->addlargefile(
                                               $newrealpath,
                                               $newzippath,
                                               0,
                                               null,
                                               $extfileattr
                                           );
                        } else {
                                           $this->addlargefile(
                                               $newrealpath,
                                               $newzippath,
                                               0,
                                               null,
                                               self::getfileextattr($newrealpath)
                                           );
                        }
                    } else if ($recursive === true) {
                        $this->adddirectoryontent(
                            $newrealpath,
                            $newzippath,
                            $recursive,
                            $followsymlinks,
                            $addedfiles,
                            $overridefilepermissions,
                            $extdirattr,
                            $extfileattr
                        );
                    } else {
                        if ($overridefilepermissions) {
                                           $this->adddirectory($zippath, 0, null, $extdirattr);
                        } else {
                                           $this->adddirectory($zippath, 0, null, self::getfileextattr($newrealpath));
                        }
                    }
                }
            }
        }
    }

    /**
     * Add a file to the archive at the specified location and file name.
     *
     * @param string $datafile    File name/path.
     * @param string $filepath    filepath and name to be used in the archive.
     * @param int    $timestamp   (Optional) Timestamp for the added file, if omitted or set to 0, the current time will be used.
     * @param string $filecomment (Optional) Comment to be added to the archive for this file.
     *                            To use filecomment, timestamp must be given.
     * @param int    $extfileattr (Optional) The external file reference, use generateextattr() to generate this.
     *
     * @return bool $success
     */
    public function addlargefile(
        $datafile,
        $filepath,
        $timestamp = 0,
        $filecomment = null,
        $extfileattr = self::EXT_FILE_ATTR_FILE
    ) {
        if ($this->isfinalized) {
            return false;
        }

        if (is_string($datafile) && is_file($datafile)) {
            $this->processfile($datafile, $filepath, $timestamp, $filecomment, $extfileattr);
        } else if (is_resource($datafile) && get_resource_type($datafile) == "stream") {
            $fh = $datafile;
            $this->openstream($filepath, $timestamp, $filecomment, $extfileattr);

            while (!feof($fh)) {
                $this->addstreamdata(fread($fh, $this->streamchunksize));
            }
            $this->closestream($this->addextrafield);
        }
        return true;
    }

    /**
     * Create a stream to be used for large entries.
     *
     * @param string $filepath    filepath and name to be used in the archive.
     * @param int    $timestamp   (Optional) Timestamp for the added file, if omitted or set to 0, the current time will be used.
     * @param string $filecomment (Optional) Comment to be added to the archive for this file.
     *                            To use filecomment, timestamp must be given.
     * @param int    $extfileattr (Optional) The external file reference, use generateextattr to generate this.
     *
     * @return bool $success
     */
    public function openstream($filepath, $timestamp = 0, $filecomment = null, $extfileattr = self::EXT_FILE_ATTR_FILE) {
        if (!function_exists('sys_get_temp_dir')) {
            die ("ERROR: Zip " . self::VERSION . " requires PHP version 5.2.1 or above if large files are used.");
        }

        if ($this->isfinalized) {
            return false;
        }

        $this->zipflush();

        if (strlen($this->streamfilepath) > 0) {
            $this->closestream();
        }

        $this->streamfile = tempnam(sys_get_temp_dir(), 'Zip');
        $this->streamdata = fopen($this->streamfile, "wb");
        $this->streamfilepath = $filepath;
        $this->streamtimestamp = $timestamp;
        $this->streamfilecomment = $filecomment;
        $this->streamfilelength = 0;
        $this->streamextfileattr = $extfileattr;

        return true;
    }

    /**
     * Add data to the open stream.
     *
     * @param string $data
     *
     * @return mixed length in bytes added or FALSE if the archive is finalized or there are no open stream.
     */
    public function addstreamdata($data) {
        if ($this->isfinalized || strlen($this->streamfilepath) == 0) {
            return false;
        }

        $length = fwrite($this->streamdata, $data, strlen($data));
        if ($length != strlen($data)) {
            die ("<p>Length mismatch</p>\n");
        }
        $this->streamfilelength += $length;

        return $length;
    }

    /**
     * Close the current stream.
     *
     * @return bool $success
     */
    public function closestream() {
        if ($this->isfinalized || strlen($this->streamfilepath) == 0) {
            return false;
        }

        fflush($this->streamdata);
        fclose($this->streamdata);

        $this->processfile(
            $this->streamfile,
            $this->streamfilepath,
            $this->streamtimestamp,
            $this->streamfilecomment,
            $this->streamextfileattr
        );

        $this->streamdata = null;
        $this->streamfilepath = null;
        $this->streamtimestamp = null;
        $this->streamfilecomment = null;
        $this->streamfilelength = 0;
        $this->streamextfileattr = null;

        // Windows is a little slow at times, so a millisecond later, we can unlink this.
        unlink($this->streamfile);

        $this->streamfile = null;

        return true;
    }

    /**
     * Processes the given file.
     *
     * @param string $datafile
     * @param sring $filepath
     * @param int $timestamp
     * @param null $filecomment
     * @param int $extfileattr
     *
     * @return false|void
     */
    private function processfile(
        $datafile,
        $filepath,
        $timestamp = 0,
        $filecomment = null,
        $extfileattr = self::EXT_FILE_ATTR_FILE
    ) {
        if ($this->isfinalized) {
            return false;
        }

        $tempzip = tempnam(sys_get_temp_dir(), 'ZipStream');

        $zip = new ZipArchive;
        if ($zip->open($tempzip) === true) {
            $zip->addfile($datafile, 'file');
            $zip->close();
        }

        $filehandle = fopen($tempzip, "rb");
        $stats = fstat($filehandle);
        $eof = $stats['size'] - 72;

        fseek($filehandle, 6);

        $gpflags = fread($filehandle, 2);
        $gztype = fread($filehandle, 2);
        fread($filehandle, 4);
        $filecrc32 = fread($filehandle, 4);
        $v = unpack("Vval", fread($filehandle, 4));
        $gzlength = $v['val'];
        $v = unpack("Vval", fread($filehandle, 4));
        $datalength = $v['val'];

        $this->buildzipentry(
            $filepath,
            $filecomment,
            $gpflags,
            $gztype,
            $timestamp,
            $filecrc32,
            $gzlength,
            $datalength,
            $extfileattr
        );

        fseek($filehandle, 34);
        $pos = 34;

        while (!feof($filehandle) && $pos < $eof) {
            $datalen = $this->streamchunksize;
            if ($pos + $this->streamchunksize > $eof) {
                $datalen = $eof - $pos;
            }
            $data = fread($filehandle, $datalen);
            $pos += $datalen;

            $this->zipwrite($data);
        }

        fclose($filehandle);

        unlink($tempzip);
    }

    /**
     * Close the archive.
     * A closed archive can no longer have new files added to it.
     *
     * @return bool $success
     */
    public function finalize() {
        if (!$this->isfinalized) {
            if (strlen($this->streamfilepath) > 0) {
                $this->closestream();
            }
            $cd = implode("", $this->cdrec);

            $cdrecsize = pack("v", count($this->cdrec));
            $cdrec = $cd . self::ZIP_END_OF_CENTRAL_DIRECTORY
            . $cdrecsize . $cdrecsize
            . pack("VV", strlen($cd), $this->offset);
            if (!empty($this->zipcomment)) {
                $cdrec .= pack("v", strlen($this->zipcomment)) . $this->zipcomment;
            } else {
                $cdrec .= "\x00\x00";
            }

            $this->zipwrite($cdrec);

            $this->isfinalized = true;
            $this->cdrec = null;

            return true;
        }
        return false;
    }

    /**
     * Get the handle ressource for the archive zip file.
     * If the zip hasn't been finalized yet, this will cause it to become finalized
     *
     * @return resource file handle
     */
    public function getzipfile() {
        if (!$this->isfinalized) {
            $this->finalize();
        }

        $this->zipflush();

        rewind($this->zipfile);

        return $this->zipfile;
    }

    /**
     * Get the zip file contents
     * If the zip hasn't been finalized yet, this will cause it to become finalized
     *
     * @return string data
     */
    public function getzipdata() {
        if (!$this->isfinalized) {
            $this->finalize();
        }
        if (!is_resource($this->zipfile)) {
            return $this->zipdata;
        } else {
            rewind($this->zipfile);
            $filestat = fstat($this->zipfile);
            return fread($this->zipfile, $filestat['size']);
        }
    }

    /**
     * Send the archive as a zip download
     *
     * @param String $filename The name of the Zip archive, in ISO-8859-1 (or ASCII) encoding,
     *                         ie. "archive.zip". Optional, defaults to NULL, which means that
     *                         no ISO-8859-1 encoded file name will be specified.
     * @param String $contenttype Content mime type. Optional, defaults to "application/zip".
     * @param String $utf8filename The name of the Zip archive, in UTF-8 encoding. Optional, defaults
     *                             to NULL, which means that no UTF-8 encoded file name will be specified.
     * @param bool $inline Use Content-Disposition with "inline" instead of "attached". Optional, defaults to FALSE.
     *
     * @return bool $success
     */
    public function sendzip($filename = null, $contenttype = "application/zip", $utf8filename = null, $inline = false) {
        if (!$this->isfinalized) {
            $this->finalize();
        }

        $headerfile = null;
        $headerline = null;
        if (!headers_sent($headerfile, $headerline)
            or die(
                "<p><strong>Error:</strong> Unable to send file $filename. HTML Headers have already been sent from "
                . "<strong>$headerfile</strong> in line <strong>$headerline</strong></p>"
            )
        ) {
            if ((ob_get_contents() === false || ob_get_contents() == '')
                or die(
                    "\n<p><strong>Error:</strong> Unable to send file <strong>$filename</strong>. "
                    . "Output buffer contains the following text (typically warnings or errors):<br>"
                    . htmlentities(ob_get_contents()) . "</p>"
                )
            ) {
                if (ini_get('zlib.output_compression')) {
                    ini_set('zlib.output_compression', 'Off');
                }

                header("Pragma: public");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s T"));
                header("Expires: 0");
                header("Accept-Ranges: bytes");
                header("Connection: close");
                header("Content-Type: " . $contenttype);
                $cd = "Content-Disposition: ";
                if ($inline) {
                    $cd .= "inline";
                } else {
                    $cd .= "attached";
                }
                if ($filename) {
                    $cd .= '; filename="' . $filename . '"';
                }
                if ($utf8filename) {
                    $cd .= "; filename*=UTF-8''" . rawurlencode($utf8filename);
                }
                header($cd);
                header("Content-Length: ". $this->getarchivesize());

                if (!is_resource($this->zipfile)) {
                    echo $this->zipdata;
                } else {
                    rewind($this->zipfile);

                    while (!feof($this->zipfile)) {
                        echo fread($this->zipfile, $this->streamchunksize);
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Return the current size of the archive
     *
     * @return $size Size of the archive
     */
    public function getarchivesize() {
        if (!is_resource($this->zipfile)) {
            return strlen($this->zipdata);
        }
        $filestat = fstat($this->zipfile);

        return $filestat['size'];
    }

    /**
     * Calculate the 2 byte dostime used in the zip entries.
     *
     * @param int $timestamp
     *
     * @return 2-byte encoded DOS Date
     */
    private function getdostime($timestamp = 0) {
        $timestamp = (int)$timestamp;
        $oldtz = @date_default_timezone_get();
        date_default_timezone_set('UTC');
        $date = ($timestamp == 0 ? getdate() : getdate($timestamp));
        date_default_timezone_set($oldtz);
        if ($date["year"] >= 1980) {
            return pack("V", (($date["mday"] + ($date["mon"] << 5) + (($date["year"] - 1980) << 9)) << 16) |
                    (($date["seconds"] >> 1) + ($date["minutes"] << 5) + ($date["hours"] << 11)));
        }
        return "\x00\x00\x00\x00";
    }

    /**
     * Build the Zip file structures
     *
     * @param string $filepath
     * @param string $filecomment
     * @param string $gpflags
     * @param string $gztype
     * @param int    $timestamp
     * @param string $filecrc32
     * @param int    $gzlength
     * @param int    $datalength
     * @param int    $extfileattr Use self::EXT_FILE_ATTR_FILE for files, self::EXT_FILE_ATTR_DIR for Directories.
     */
    private function buildzipentry(
        $filepath,
        $filecomment,
        $gpflags,
        $gztype,
        $timestamp,
        $filecrc32,
        $gzlength,
        $datalength,
        $extfileattr
    ) {
        $filepath = str_replace("\\", "/", $filepath);
        $filecommentlength = (empty($filecomment) ? 0 : strlen($filecomment));
        $timestamp = (int)$timestamp;
        $timestamp = ($timestamp == 0 ? time() : $timestamp);

        $dostime = $this->getdostime($timestamp);
        $tspack = pack("V", $timestamp);

        $ux = "\x75\x78\x0B\x00\x01\x04\xE8\x03\x00\x00\x04\x00\x00\x00\x00";

        if (!isset($gpflags) || strlen($gpflags) != 2) {
            $gpflags = "\x00\x00";
        }

        $isfileutf8 = mb_check_encoding($filepath, "UTF-8")
            && !mb_check_encoding($filepath, "ASCII");
        $iscommentutf8 = !empty($filecomment)
            && mb_check_encoding($filecomment, "UTF-8")
            && !mb_check_encoding($filecomment, "ASCII");
        if ($isfileutf8 || $iscommentutf8) {
            $flag = 0;
            $gpflagsv = unpack("vflags", $gpflags);
            if (isset($gpflagsv['flags'])) {
                $flag = $gpflagsv['flags'];
            }
            $gpflags = pack("v", $flag | (1 << 11));
        }

        // File name length.
        $header = $gpflags
            . $gztype
            . $dostime
            . $filecrc32
            . pack("VVv", $gzlength, $datalength, strlen($filepath));

        $zipentry  = self::ZIP_LOCAL_FILE_HEADER;
        $zipentry .= self::ATTR_VERSION_TO_EXTRACT;
        $zipentry .= $header;
        // Extra field length.
        $zipentry .= pack("v", ($this->addextrafield ? 28 : 0));
        // Filename.
        $zipentry .= $filepath;
        // Extra fields.
        if ($this->addextrafield) {
            $zipentry .= "\x55\x54\x09\x00\x03" . $tspack . $tspack . $ux;
        }
        $this->zipwrite($zipentry);

        $cdentry  = self::ZIP_CENTRAL_FILE_HEADER;
        $cdentry .= self::ATTR_MADE_BY_VERSION;
        $cdentry .= ($datalength === 0 ? "\x0A\x00" : self::ATTR_VERSION_TO_EXTRACT);
        $cdentry .= $header;
        // Extra field length.
        $cdentry .= pack("v", ($this->addextrafield ? 24 : 0));
        // File comment length.
        $cdentry .= pack("v", $filecommentlength);
        // Disk number start.
        $cdentry .= "\x00\x00";
        // Internal file attributes.
        $cdentry .= "\x00\x00";
        // External file attributes.
        $cdentry .= pack("V", $extfileattr);
        // Relative offset of local header.
        $cdentry .= pack("V", $this->offset);
        // Filename.
        $cdentry .= $filepath;
        // Extra fields.
        if ($this->addextrafield) {
            $cdentry .= "\x55\x54\x05\x00\x03" . $tspack . $ux;
        }
        if (!empty($filecomment)) {
            // Comment.
            $cdentry .= $filecomment;
        }

        $this->cdrec[] = $cdentry;
        $this->offset += strlen($zipentry) + $gzlength;
    }

    /**
     * Writes to the ZIP file.
     *
     * @param string $data
     */
    private function zipwrite($data) {
        if (!is_resource($this->zipfile)) {
            $this->zipdata .= $data;
        } else {
            fwrite($this->zipfile, $data);
            fflush($this->zipfile);
        }
    }

    /**
     * Dumps the content to the ZIP file.
     */
    private function zipflush() {
        if (!is_resource($this->zipfile)) {
            $this->zipfile = tmpfile();
            fwrite($this->zipfile, $this->zipdata);
            $this->zipdata = null;
        }
    }

    /**
     * Join $file to $dir path, and clean up any excess slashes.
     *
     * @param string $dir
     * @param string $file
     */
    public static function pathjoin($dir, $file) {
        if (empty($dir) || empty($file)) {
            return self::getrelativepath($dir . $file);
        }
        return self::getrelativepath($dir . '/' . $file);
    }

    /**
     * Clean up a path, removing any unnecessary elements such as /./, // or redundant ../ segments.
     * If the path starts with a "/", it is deemed an absolute path and any /../ in the beginning is stripped off.
     * The returned path will not end in a "/".
     *
     * Sometimes, when a path is generated from multiple fragments,
     *  you can get something like "../data/html/../images/image.jpeg"
     * This will normalize that example path to "../data/images/image.jpeg"
     *
     * @param string $path The path to clean up
     *
     * @return string the clean path
     */
    public static function getrelativepath($path) {
        $path = preg_replace("#/+\.?/+#", "/", str_replace("\\", "/", $path));
        $dirs = explode("/", rtrim(preg_replace('#^(?:\./)+#', '', $path), '/'));

        $offset = 0;
        $sub = 0;
        $suboffset = 0;
        $root = "";

        if (empty($dirs[0])) {
            $root = "/";
            $dirs = array_splice($dirs, 1);
        } else if (preg_match("#[A-Za-z]:#", $dirs[0])) {
            $root = strtoupper($dirs[0]) . "/";
            $dirs = array_splice($dirs, 1);
        }

        $newdirs = array();
        foreach ($dirs as $dir) {
            if ($dir !== "..") {
                $suboffset--;
                $newdirs[++$offset] = $dir;
            } else {
                $suboffset++;
                if (--$offset < 0) {
                    $offset = 0;
                    if ($suboffset > $sub) {
                        $sub++;
                    }
                }
            }
        }

        if (empty($root)) {
            $root = str_repeat("../", $sub);
        }
        return $root . implode("/", array_slice($newdirs, 0, $offset));
    }

    /**
     * Create the file permissions for a file or directory, for use in the extfileattr parameters.
     *
     * @param int   $owner Unix permisions for owner (octal from 00 to 07)
     * @param int   $group Unix permisions for group (octal from 00 to 07)
     * @param int   $other Unix permisions for others (octal from 00 to 07)
     * @param bool  $isfile
     *
     * @return external|false field.
     */
    public static function generateextattr($owner = 07, $group = 05, $other = 05, $isfile = true) {
        $fp = $isfile ? self::S_IFREG : self::S_IFDIR;
        $fp |= (($owner & 07) << 6) | (($group & 07) << 3) | ($other & 07);

        return ($fp << 16) | ($isfile ? self::S_DOS_A : self::S_DOS_D);
    }

    /**
     * Get the file permissions for a file or directory, for use in the extfileattr parameters.
     *
     * @param string $filename
     * @return external|false ref field, or FALSE if the file is not found.
     */
    public static function getfileextattr($filename) {
        if (file_exists($filename)) {
            $fp = fileperms($filename) << 16;
            return $fp | (is_dir($filename) ? self::S_DOS_D : self::S_DOS_A);
        }
        return false;
    }
}
