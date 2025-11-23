<?php

declare(strict_types=1);

namespace ManaPHP\Security;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Security\Crypt\Exception as CryptException;
use function md5;
use function openssl_cipher_iv_length;
use function openssl_decrypt;
use function openssl_encrypt;
use function openssl_random_pseudo_bytes;
use function pack;
use function strlen;
use function substr;
use function unpack;

class Crypt implements CryptInterface
{
    #[Autowired] protected string $master_key;
    #[Autowired] protected string $method = 'AES-128-CBC';

    public function encrypt(string $text, string $key): string
    {
        $iv_length = openssl_cipher_iv_length($this->method);
        /** @noinspection CryptographicallySecureRandomnessInspection */
        if (!$iv = openssl_random_pseudo_bytes($iv_length)) {
            throw new CryptException('Failed to generate IV.', ['method' => $this->method, 'iv_length' => $iv_length, 'openssl_error' => openssl_error_string()]);
        }

        $data = pack('N', strlen($text)) . $text . md5($text, true);
        return $iv . openssl_encrypt($data, $this->method, md5($key, true), OPENSSL_RAW_DATA, $iv);
    }

    public function decrypt(string $text, string $key): string
    {
        $iv_length = openssl_cipher_iv_length($this->method);

        if (strlen($text) < $iv_length * 2) {
            throw new CryptException('Encrypted data is too short.', ['data_length' => strlen($text), 'required_length' => $iv_length * 2, 'method' => $this->method]);
        }

        $data = substr($text, $iv_length);
        $iv = substr($text, 0, $iv_length);
        $decrypted = openssl_decrypt($data, $this->method, md5($key, true), OPENSSL_RAW_DATA, $iv);

        $length = unpack('N', $decrypted)[1];

        if (4 + $length + 16 !== strlen($decrypted)) {
            throw new CryptException('Decrypted data length is wrong.', ['expected_length' => 4 + $length + 16, 'actual_length' => strlen($decrypted), 'unpacked_length' => $length]);
        }

        $plainText = substr($decrypted, 4, -16);

        if (md5($plainText, true) !== substr($decrypted, -16)) {
            throw new CryptException('Decrypted MD5 checksum is not valid.', ['data_length' => strlen($plainText), 'expected_md5' => bin2hex(substr($decrypted, -16)), 'calculated_md5' => bin2hex(md5($plainText, true))]);
        }

        return $plainText;
    }

    public function getDerivedKey(string $type): string
    {
        return md5($this->master_key . ':' . $type);
    }
}
