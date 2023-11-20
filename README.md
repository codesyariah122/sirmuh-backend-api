### Development Process
```
composer update
composer install
php artisan key:generate
php artisan passport:install

php artisan migrate
php artisan db:seed
```


##### New Environment  
```
BROADCAST_DRIVER=pusher


PUSHER_APP_ID=1595884
PUSHER_APP_KEY=7a54d3bea70e636f64fa
PUSHER_APP_SECRET=70b42427b18040eece39
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1
```

***Check seeder***  
for php artisan db:seed , check seeder file before start seeder process  

### Requirements Development  
- PHP 8.2
- Laravel 9.1
- Environment : 
    Laradock / docker / container
    nginx / mariaDB  

### sirmuh-api.service 

```
[Service]
ExecStart=/home/xyz/routeros-api/sirmuh-api/start.sh
WorkingDirectory=/home/xyz/routeros-api/sirmuh-api
Restart=always
User=www-data
Group=www-data

[Install]
WantedBy=multi-user.target

```


**Start System Service**  
```
systemctl daemon-reload
systemctl start sirmuh-api
systemctl status sirmuh-api
```  
**Output:**  
```
● sirmuh-api.service
     Loaded: loaded (/etc/systemd/system/sirmuh-api.service; enabled; vendor preset: enabled)
     Active: active (running) since Fri 2023-11-17 20:01:17 WIB; 2s ago
   Main PID: 18205 (start.sh)
      Tasks: 2 (limit: 4354)
     Memory: 10.4M
     CGroup: /system.slice/sirmuh-api.service
             ├─18205 /bin/bash /home/xyz/routeros-api/sirmuh-api/start.sh
             └─18206 php -S localhost:4041 -t public

Nov 17 20:01:17 xyz-DreamSys systemd[1]: Started sirmuh-api.service.
Nov 17 20:01:17 xyz-DreamSys start.sh[18206]: [Fri Nov 17 20:01:17 2023] PHP 8.2.12 Development Server (http://localhost:4041)

```  
========================================================================================

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
