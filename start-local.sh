#!/bin/zsh

cd "$(dirname "$0")" || exit 1

echo "Linh Florist dang chay tai http://localhost:4174"
echo "Hay bat MySQL trong XAMPP Manager truoc khi dang ky hoac dang nhap."

exec /Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:4174
