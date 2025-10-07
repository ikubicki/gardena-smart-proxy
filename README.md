# gardena-smart-proxy
A simple PHP web service to handle callbacks and proxy API calls. Useful when you can't setup websocket connections to pull devices/services updates

## installation

You can either build a phar application and put content of built `/dist` directory, or run `composer install` and put all the files and dirs (including vendor) on the server.

If you want to run the application in docker container, remember about exposing proper ports to the container.