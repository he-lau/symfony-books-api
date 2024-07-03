# symfony-books-api

Une simple API de livres.

- `composer require symfony/serializer-pack` : https://symfony.com/doc/current/components/serializer.html
- `composer require sensio/framework-extra-bundle` : ParamConverter : (int $id, EntityRepository $repo) --> (Entity $entity)
- `symfony console make:subscriber` : Gestion des événements (observeur,observable)
- `composer require symfony/validator`
- `composer require doctrine/annotations`
- `composer require lexik/jwt-authentication-bundle` : JWT (JSON Web Token)
- `openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa _keygen_bits:4096` : Génerer clé avec RSA & chiffrement clé avec AES-256 & taille 4096 bits
- `openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout` :
