import CryptoJS from 'crypto-js';

/**
 * 生成随机 IV
 * @returns 随机生成的 16 字节（16 个字符）的 IV
 */
export function getRandomIV(): string {
    const characters = '0123456789ABCDEF';
    let randomString = '';
    for (let i = 0; i < 16; i++) {
        randomString += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    // console.log(`Generated IV: ${randomString}`);
    return randomString;
}

/**
 * 使用 AES 加密内容
 * @param content 要加密的内容
 * @param key 加密密钥
 * @param iv 初始向量
 * @returns 加密后的字符串（hex 格式）
 */
export function encryptAES(content: string, key: string, iv: string): string {
    const keyUtf8 = CryptoJS.enc.Utf8.parse(key);
    const ivUtf8 = CryptoJS.enc.Utf8.parse(iv);

    const encrypted = CryptoJS.AES.encrypt(content, keyUtf8, {
        iv: ivUtf8,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7,
    });

    // 返回密文的 hex 字符串形式
    return encrypted.ciphertext.toString(CryptoJS.enc.Hex);
}
