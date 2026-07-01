<?php

class AxmlReader {
    private $data;
    private $pos = 0;
    private $strings = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    private function readInt32() {
        if ($this->pos + 4 > strlen($this->data)) {
            return 0;
        }
        $val = unpack('V', substr($this->data, $this->pos, 4));
        $this->pos += 4;
        return $val[1];
    }
    
    private function readInt16() {
        if ($this->pos + 2 > strlen($this->data)) {
            return 0;
        }
        $val = unpack('v', substr($this->data, $this->pos, 2));
        $this->pos += 2;
        return $val[1];
    }
    
    public function parse() {
        $magic = $this->readInt32();
        if ($magic !== 0x00080003) {
            throw new Exception("Invalid AndroidManifest.xml magic header: " . dechex($magic));
        }
        
        $fileSize = $this->readInt32();
        $len = strlen($this->data);
        
        $packageName = '';
        $versionCode = 0;
        $versionName = '';
        $appName = '';
        
        while ($this->pos < $len) {
            $chunkType = $this->readInt32();
            $chunkSize = $this->readInt32();
            $chunkEnd = $this->pos - 8 + $chunkSize;
            
            if ($chunkType === 0x001C0001) { // String Pool
                $stringCount = $this->readInt32();
                $styleCount = $this->readInt32();
                $flags = $this->readInt32();
                $stringStart = $this->readInt32();
                $styleStart = $this->readInt32();
                
                $offsets = [];
                for ($i = 0; $i < $stringCount; $i++) {
                    $offsets[] = $this->readInt32();
                }
                
                $poolStart = $this->pos - (8 + 20 + $stringCount * 4) + $stringStart;
                
                $isUtf8 = ($flags & 0x00000100) !== 0;
                
                for ($i = 0; $i < $stringCount; $i++) {
                    $offset = $poolStart + $offsets[$i];
                    if ($offset >= $len) {
                        $this->strings[] = '';
                        continue;
                    }
                    if ($isUtf8) {
                        $lenByte = ord($this->data[$offset]);
                        $idx = 1;
                        if ($lenByte & 0x80) {
                            $lenByte = (($lenByte & 0x7F) << 8) | ord($this->data[$offset + 1]);
                            $idx = 2;
                        }
                        $byteCount = ord($this->data[$offset + $idx]);
                        $idx++;
                        if ($byteCount & 0x80) {
                            $byteCount = (($byteCount & 0x7F) << 8) | ord($this->data[$offset + $idx]);
                            $idx++;
                        }
                        $this->strings[] = substr($this->data, $offset + $idx, $byteCount);
                    } else {
                        $charCount = unpack('v', substr($this->data, $offset, 2))[1];
                        $idx = 2;
                        if ($charCount & 0x8000) {
                            $charCount = (($charCount & 0x7FFF) << 16) | unpack('v', substr($this->data, $offset + 2, 2))[1];
                            $idx = 4;
                        }
                        $utf16 = substr($this->data, $offset + $idx, $charCount * 2);
                        $this->strings[] = mb_convert_encoding($utf16, 'UTF-8', 'UTF-16LE');
                    }
                }
            } elseif ($chunkType === 0x00100102) { // Start Element (Tag)
                $line = $this->readInt32();
                $comment = $this->readInt32();
                $ns = $this->readInt32();
                $nameIdx = $this->readInt32();
                $tagName = $this->strings[$nameIdx] ?? '';
                
                $attrStart = $this->readInt16();
                $attrSize = $this->readInt16();
                $attrCount = $this->readInt16();
                $idIndex = $this->readInt16();
                $classIndex = $this->readInt16();
                $styleIndex = $this->readInt16();
                
                // Read attributes
                for ($i = 0; $i < $attrCount; $i++) {
                    $attrNs = $this->readInt32();
                    $attrNameIdx = $this->readInt32();
                    $attrValIdx = $this->readInt32();
                    $attrType = $this->readInt32() >> 24;
                    $attrData = $this->readInt32();
                    
                    $attrName = $this->strings[$attrNameIdx] ?? '';
                    
                    if ($tagName === 'manifest') {
                        if ($attrName === 'package') {
                            $packageName = $this->strings[$attrValIdx] ?? '';
                        } elseif ($attrName === 'versionCode') {
                            if ($attrType === 16 || $attrType === 17) {
                                $versionCode = $attrData;
                            } else {
                                $versionCode = intval($this->strings[$attrValIdx] ?? '0');
                            }
                        } elseif ($attrName === 'versionName') {
                            $versionName = $this->strings[$attrValIdx] ?? '';
                        }
                    } elseif ($tagName === 'application') {
                        if ($attrName === 'label') {
                            if ($attrValIdx !== -1 && isset($this->strings[$attrValIdx])) {
                                $appName = $this->strings[$attrValIdx];
                            }
                        }
                    }
                }
            }
            
            $this->pos = $chunkEnd;
        }
        
        return [
            'packageName' => $packageName,
            'versionName' => $versionName,
            'versionCode' => $versionCode,
            'appName' => $appName
        ];
    }
}

class ApkParser {
    private $zip;
    private $manifestData;
    
    public function __construct($apkPath) {
        if (!file_exists($apkPath)) {
            throw new Exception("APK file not found: " . $apkPath);
        }
        
        $this->zip = new ZipArchive();
        if ($this->zip->open($apkPath) !== TRUE) {
            throw new Exception("Unable to open APK file (invalid zip archive).");
        }
        
        $this->manifestData = $this->zip->getFromName('AndroidManifest.xml');
        $this->zip->close();
        
        if (!$this->manifestData) {
            throw new Exception("AndroidManifest.xml not found in APK.");
        }
    }
    
    public function parse() {
        $axml = new AxmlReader($this->manifestData);
        return $axml->parse();
    }
}
