SWIRF GAME API

using Lumen, [this] (http://lumen.laravel.com/docs) is the official documentation

step by step configuration :
1. clone
2. run ```composer install```
3. copy file ```[project name]/environtment/env.[your environtment]``` to ```[project name]/.env``` or if you using bash, run ```sh install.sh [your environtment]```

the Document Root is under ```[project name]/public``` if you want to setup vhost, or you may use the [Laravel Homestead] (https://laravel.com/docs/5.4/homestead) virtual machine, Laravel [Valet] (https://laravel.com/docs/5.4/valet), or the built-in PHP development server: 

```php -S localhost:8000 -t public```