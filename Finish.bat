// >/dev/null 2>&1 || # >/dev/null 2>&1 || goto :s
// >/dev/null 2>&1 || :<<"::BATCH"
/*
:s
taskkill /IM php.exe /f
taskkill /IM php-cgi.exe /f
del /f "temp/server;;in" /y
del /f "temp/server;;out" /y
del "temp" /y
goto :EOF
::BATCH
pkill php
pkill php-cgi
unlink "temp/server;;in"
unlink "temp/server;;out"
rmdir temp
cd $PWD
