@echo on
php -d extension=ext\php_imagick.dll -d session.save_path=.\session -S 127.0.0.1:3000 -t .\htdocs .\htdocs\index.php