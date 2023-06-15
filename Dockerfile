# Set the base image
FROM baiyamao/ncpl:1.0

COPY . /var/www

WORKDIR /var/www

RUN npm install && composer install

EXPOSE 8000

CMD ["php","artisan","serve"]
