#!/usr/bin/env bash
#
# Ejecuta la suite de tests del proyecto de forma segura.
#
#  - Usa PHP 8.3 (la versión de producción). OJO: el "php" por defecto del servidor es 8.4,
#    que es incompatible con el composer.lock; por eso se fuerza php8.3.
#  - Corre como www-data para no romper permisos de cache/storage.
#  - Los tests usan una base de datos AISLADA (shopify_integrator_test). La conexión está
#    fijada en phpunit.xml y tests/TestCase.php tiene una salvaguarda que aborta si por error
#    se apuntara a una base que no sea de pruebas. Producción NUNCA se toca.
#
# Uso:
#   bash run-tests.sh                      # corre toda la suite
#   bash run-tests.sh --filter=EmisionTest # corre un grupo
#
set -e
cd "$(dirname "$0")"
sudo -u www-data HOME=/tmp php8.3 vendor/bin/phpunit "$@"
