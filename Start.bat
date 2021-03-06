// >/dev/null 2>&1 || # >/dev/null 2>&1 || goto :s
// >/dev/null 2>&1 || :<<"::BATCH"
/*
:s
cls
@echo off
set lws=Lion Web Server
title %lws%
setlocal
set binary_dir=%windir:~0,3%php\bin
where php-cgi.exe >nul 2>&1 || goto :check
@chcp 65001>NUL
if %~1==php if %~2==upd goto :update
php.exe "Server/Start.php"
goto :P
:check
if not EXIST "%binary_dir%\php-cgi.exe" goto :check2
	call :pup "%PATH%;%binary_dir%"
	echo PHP binaries found in %binary_dir%! 'Path' variable has been updated
	echo Please, restart your system to use it now.
goto :P
:check2
if not EXIST "%CD%\php\php-cgi.exe" goto :check3
	call :pup "%PATH%;%CD%\php"
	echo PHP binaries found in %CD%\php! 'Path' variable has been updated
	echo Please, restart your system to use it now.
goto :P
:check3
if not EXIST "%CD%\bin\php-cgi.exe" goto :not-found
	call :pup "%PATH%;%CD%\bin"
	echo PHP binaries found in %CD%\bin! 'Path' variable has been updated
	echo Please, restart your system to use it now.
goto :P
:not-found
title %lws% --Installation[0%]
if  EXIST "%CD%/php.zip" goto :install
echo Checking internet connection...
ping windows.php.net >NUL
if %ErrorLevel% GTR 0 call :Err "Error: Cannot connect to the PHP official site"
echo DONE!
echo Gathering latest PHP binaries download link...
title %lws% --Installation[3%]
set res=""
set /a pass=1
:check-new
set pcx="64"
echo "%PROCESSOR_ARCHITECTURE%" | findstr /i "64" >NUL || set pcx="86"
for /f "delims=>" %%I in ('cscript /nologo /e:jscript "%~f0" "https://windows.php.net/downloads/releases/latest"') do call :loopchecker "%%I", res
	title %lws% --Installation[5%]
	echo DONE!
	if %pass%==0 goto :launch-check
	cscript /nologo /e:jscript "%~f0" "%res%" "%CD%\php.zip"
	:inst-downloaded
	if not EXIST "%CD%/php.zip" call :Err "Error extracting php.zip\r\nTry downloading php binaries from https://windows.php.net/downloads/releases/latest/"
	echo PHP extracted to %binary_dir%
	title %lws% --Installation[50%]
	:install
	title %lws% --Installation[55%]
	if not EXIST "%binary_dir%" mkdir %binary_dir%
	cscript /nologo /e:jscript "%~f0" "%CD%\php.zip" "%binary_dir%" "1"
	title %lws% --Installation[98%]
	echo Writing to the 'path' variable...
call :pup "%PATH%;%binary_dir%"
	echo DONE!
	echo Copying configuration...
	call :ini-update "%binary_dir%\php.ini" "%binary_dir%\php.ini-development"
	if not EXIST "%binary_dir%\php.ini" call :Err "Error: Cannot copy PHP.ini"
	title %lws% --Installation[100%]
	echo DONE!
	if %pass%==0 (
			echo PHP HAS BEEN SUCCESSFULLY UPDATED!
			goto :P
		)
	echo PHP HAS BEEN SUCCESSFULLY INSTALLED!
	echo Please, relog into your system to start-up server
	pause
	goto :P
endlocal
	:loopchecker
	
	set /A check=0
	echo "%~1" | findstr /i "nts" >NUL || set /A check=%check%+1
	echo "%~1" | findstr /i "devel" >NUL || set /A check=%check%+1
	echo "%~1" | findstr /i "debug" >NUL || set /A check=%check%+1
	
	IF %check%==3 goto :check1_passed
		EXIT /B 0
		
	:check1_passed
	
		set /A check=0
		echo "%~1" | findstr /i "Win32" >NUL || set /A check=1
		echo "%~1" | findstr /i "x%pcx%-latest.zip" >NUL || set /A check=1
		
		IF %check%==0 goto :check2_passed
			EXIT /B 0
			
		:check2_passed
		if "%~1"=="" EXIT /B 0
			for /f "tokens=1,2,3,4,5,6 " %%a in ("%~1") do set res=%%f
		set res=%res:~7%
		set res=https://windows.php.net/%res:"=%
	EXIT /B 0

	:Err
		echo %~1
		goto :P
	EXIT /B 1
	:pup
		cscript /nologo /e:jscript "%~f0" "0" %~1
	EXIT /B 0
	:check-num-set
	setlocal
		set /A i=0
		set inp=%~2
		:check-num-set-loop
			set /a ii=%i% + 1
			::loop-lace trick *batch bugs are annoying
			goto :EOF
			if !%inp:~%i%,%ii%%! == "" goto :check-num-return
			set /a anum=0
			if !%inp:~%i%,%ii%%! == "." set /a anum=1
			if !%inp:~%i%,%ii%%! == "," set /a anum=1
			if !%inp:~%i%,%ii%%! == 9 set /a anum=1
			::if !%inp:~%i%,%ii%! == 0 set /a anum=1
			if !%inp:~%i%,%ii%%! == 1 set /a anum=1
			if !%inp:~%i%,%ii%%! == 2 set /a anum=1
			if !%inp:~%i%,%ii%%! == 3 set /a anum=1
			if !%inp:~%i%,%ii%%! == 4 set /a anum=1
			if !%inp:~%i%,%ii%%! == 5 set /a anum=1
			if !%inp:~%i%,%ii%%! == 6 set /a anum=1
			if !%inp:~%i%,%ii%%! == 7 set /a anum=1 
			if !%inp:~%i%,%ii%%! == 8 set /a anum=1
			if anum==0 EXIT /B 0
			set /A i += 1
			goto :check-num-set-loop
		:check-num-return
		(endlocal & set "%~1=%~2")
	EXIT /B 0
	:ini-update
	setlocal disableDelayedExpansion
	set rep2=short_open_tag = Off
	set rep3=memory_limit = 128M
	>"%~1" (
	  for /f "usebackq delims=" %%A in ("%~2") do (
		if "%%A" == "%rep2%" ( echo short_open_tag = On ) else ( if "%%A" == "%rep3%" ( 
			echo memory_limit = 1028M
			echo extension_dir = "ext"
			) else ( echo %%A ) )
		)
	)
	endlocal
	EXIT /B 0
	:update
		set /A pass=0
		goto :check-new
	:launch-check
		for /f "tokens=1,2 delims= " %%a in (php.exe "-v") do call :check-num-set cur, %%b
		for /f "tokens=1,2 delims=-" %%a in ("%res%") do set new=%%b
	if "%new%" == "%cur%" goto :no-new
		echo NEW PHP VERSION IS AVAILABLE (%new%)
	 cscript /nologo /e:jscript "%~f0" "%res%" "%CD%\php.zip" 
	goto :inst-downloaded
	:no-new
		echo Current PHP version is the latest version
:P
pause
goto :EOF
::BATCH
_cmp_=$(dpkg-query -W --showformat='${Status}\n' php|grep "i");
if [ "" == "$_cmp_" ]; then
    sudo apt-get --yes install php;
    sudo apt-remove --yes apache2;
fi
_cmp_=$(dpkg-query -W --showformat='${Status}\n' php-cgi|grep "i");
if [ "" == "$_cmp_" ]; then
    sudo apt-get --yes install php-cgi;
fi

if [ -f "temp/server;;in" ]; then
    sudo php "Server/Start.php" $@;
else
    echo -e '\033]2;Lion Web Server\007';
    sudo php "Server/Start.php" & sleep 100
fi
:<<"//JScript"
*/
if( WSH.Arguments.Count() > 0){
if(WSH.Arguments.Count() < 3){
	if( WSH.Arguments(0) == "0" && WSH.Arguments.Count() == 2 )
	{
		var x = new ActiveXObject( "WScript.Shell" ).Environment("SYSTEM");
		x("PATH") = WSH.Arguments(1);
	} else {
		var x = new ActiveXObject("MSXML2.XMLHTTP");
		x.open("GET",WSH.Arguments(0),true);
		x.Send();
	};
};
if( WSH.Arguments.Count() == 1)
{
	while( x.readyState!=4 ){ WSH.Sleep(50); };
	WSH.Echo(x.responseText);
} else if( WSH.Arguments.Count() == 2 && WSH.Arguments(0) != "0" ) {
	var liver = WSH.Arguments(0);
	liver = (liver.split("/")[6]).split("-");
	liver = "ver " + liver[1] + '[' + liver[4] + ']';
	WSH.Echo( "Downloading PHP binaries " + liver + "..." );
	
	while( x.readyState!=4 ){ WSH.Sleep(50); };
	//Status between 200 and 399
	if( x.Status > 199 && x.Status < 400 ){
	var echou = "~";
	var len="Downloading PHP binaries " + liver + "...";

	for(i = 0; i<len.length; i++ )
		echou = echou + "~";
	WSH.Echo(echou);
	var oStream = new ActiveXObject("ADODB.Stream")
	oStream.Open();
	oStream.Type = 1; //adTypeBinary
	
	oStream.Write( x.ResponseBody );
	oStream.Position = 0;
	
		var fso  = new ActiveXObject("Scripting.FileSystemObject");
		if( fso.FileExists( WSH.Arguments(1) ) ){
			fso.DeleteFile( WSH.Arguments(1) );
		}
		if( fso.FileExists( WSH.Arguments(1) ) )
		{
			WSH.Echo("Error: file php.zip is unaccessable!");
		} else {
			fso = null;
			oStream.SaveToFile( WSH.Arguments(1) );
			oStream.Close();
			oStream = null;
			var dwlMb = x.GetResponseHeader("Content-Length")/1048576 + "";
			dwlMb = dwlMb.split(".");
			WSH.Echo("Downloaded: " + dwlMb[0] + "." + dwlMb[1].split("")[0] + "Mb =~> PHP.zip");
			WSH.Echo(echou);
		};
	} else {
		WSH.Echo("Error: cannot download latest php tarball\nAborting...");
	};
} else {
	var Zip = WSH.Arguments(0);
	var ExtractionDir = WSH.Arguments(1);
	WSH.Echo("Extracting php.zip,,,");
	if( WSH.Arguments.Count() > 3 ){
		var PrompTo = WSH.Arguments(3);
	} else {
		var PrompTo = false;
	};
    
    var shO = new ActiveXObject("Shell.Application");
    var x = shO.NameSpace(Zip).Items();
    var target = shO.NameSpace(ExtractionDir);

    //You can view other opts at http://msdn2.microsoft.com/en-us/library/ms723207.aspx
	var intOptions = 0;
	if( PrompTo == false )
		intOptions = 16;

    target.CopyHere(x, intOptions);
    target = null
    shO  = null
	var fso  = new ActiveXObject("Scripting.FileSystemObject");
	if( WSH.Arguments(2)=="1" ){
		fso.DeleteFile( Zip );
	};
	fso = null
};
x = null;
}
//JScript
