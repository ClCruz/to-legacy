# LEGACY 
### Builded in PHP.  

#### How to install  

#### Install for:  

```bash
sudo docker run -it -d -p 2004:80 --restart=always --name unique.legacy \
-v /var/www/unique/legacy:/var/www/html \
blcoccaro/phpwithsql:v1
```


### meanings 
| location | what |
| -------- | ----- |
| /var/www/unique/legacy | where is the source of legacy |

### .htaccess
```.htaccess
<IfModule mod_rewrite.c>
RedirectMatch 404 \.json
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^([^\.]+)$ $1.php [NC,L]
</IfModule>
```

![MC HAMMER](https://camo.githubusercontent.com/294d473d32d1d33750ea6a059bcd44cf31398535/687474703a2f2f692e696d6775722e636f6d2f6163484d3330786c2e6a7067)