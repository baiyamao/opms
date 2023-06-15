# Set the base image
FROM baiyamao/ncpl:1.0

COPY . $WORKDIR

WORKDIR $WORKDIR

RUN npm install && composer install

EXPOSE 8000

CMD ["php","artisan","serve"]