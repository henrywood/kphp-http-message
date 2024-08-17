# http message

[![License](https://img.shields.io/packagist/l/phppkg/http-message.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/badge/php-%3E=8.0.0-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/phppkg/http-message)
[![Latest Stable Version](http://img.shields.io/packagist/v/phppkg/http-message.svg)](https://packagist.org/packages/phppkg/http-message)

http message implementing PSR 7 for KPHP and PHP。

## Install

- Edit `composer.json`

Open `composer.json`，add `require` 

```
"henrywood/kphp-http-message": "`2.0",
```

Save and then execute: `composer update`

- 通过 `composer require`

```bash
composer require henrywood/kphp-http-message
```

- git拉取

```bash
git clone https://github.com/henrywood/kphp-http-message.git // github
```

## 使用

### 基本使用

```php
use PhpPkg\Http\Message\Request;
use PhpPkg\Http\Message\Response;

$request = new Request($method, $uri);
$request = new ServerRequest(... ...);
$response = new Response($code);
... ...
```

Factory Method

Use the provided factory method to quickly create the desired instance object.

```php
use PhpPkg\Http\Message\HttpFactory;

$request = HttpFactory::createRequest($method, $uri);

// server request
$request = HttpFactory::createServerRequest('GET', 'http://www.abc.com/home');
$request = HttpFactory::createServerRequestFromArray($_SERVER);

$response = HttpFactory::createResponse($code);
```

### 扩展

```php
use PhpPkg\Http\Message\Request;
use PhpPkg\Http\Message\Traits\ExtendedRequestTrait;

class MyRequest extends Request {
   use ExtendedRequestTrait; // 里面提供的更多方便使用的方法
}

// 

$request = new MyRequest(...);

$age = $request->getInt('age');
$name = $request->getTrimmed('name');
```

```php
use PhpPkg\Http\Message\Response;
use PhpPkg\Http\Message\Traits\ExtendedResponseTrait;

class MyResponse extends Response {
   use ExtendedResponseTrait;
}
```

## 项目地址

- **github** https://github.com/henrywood/kphp-http-message

## License

[MIT](LICENSE)
