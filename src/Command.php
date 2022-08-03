<?php

class Command
{
    const CIPHER = "aes-128-cbc";
    const PAD = '*';
    const ENCRYPT = 'encrypt';
    const DECRYPT = 'decrypt';
    const PROMPT = "Enter password: ";
    const ERROR = "File error\n";
    const USAGE = "Usage: <" . self::ENCRYPT . "|" . self::DECRYPT . "> <source file> <destination file> <username> [password]\n";

    public static function recursive($path, $cmd, $srcFile, $dstFile, $username, $password)
    {
        $path = realpath($path);
        if (is_dir($path)) {
            foreach (scandir($path) as $fileName) {
                if ($fileName !== '.' && $fileName !== '..') {
                    self::recursive($path . DIRECTORY_SEPARATOR . $fileName, $cmd, $srcFile, $dstFile, $username, $password);
                }
            }
        } elseif (is_file($path) && pathinfo($path, PATHINFO_BASENAME) === $srcFile) {
            $dstPath = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . $dstFile;
            ($cmd === self::ENCRYPT) ? self::enc($path, $dstPath, $username, $password) : self::dec($path, $dstPath, $username, $password);
        }
        return 0;
    }

    private static function enc($srcFile, $dstFile, $username, $password)
    {
        $length = openssl_cipher_iv_length(self::CIPHER);
        $cipherText = openssl_encrypt(
            file_get_contents($srcFile),
            self::CIPHER, $password,
            OPENSSL_RAW_DATA,
            substr(str_pad($username, $length, self::PAD), 0, $length)
        );
        if ($cipherText === false) {
            exit(openssl_error_string());
        }
        if (file_put_contents($dstFile, base64_encode($cipherText)) === false) {
            exit(self::ERROR);
        }
        echo "Encrypted '{$srcFile}' to '{$dstFile}'\n";
    }

    public static function dec($srcFile, $dstFile, $username, $password)
    {
        $length = openssl_cipher_iv_length(self::CIPHER);
        $text = openssl_decrypt(
            base64_decode(file_get_contents($srcFile)),
            self::CIPHER,
            $password,
            OPENSSL_RAW_DATA,
            substr(str_pad($username, $length, self::PAD), 0, $length)
        );
        if ($text === false) {
            exit(openssl_error_string());
        }
        if (file_put_contents($dstFile, $text) === false) {
            exit(self::ERROR);
        }
        echo "Decrypted '{$srcFile}' to '{$dstFile}'\n";
    }
}
