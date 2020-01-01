@if (@a==@b) @end /*

@echo off
@chcp 65001>NUL
title Lion Web Server
where php-cgi.exe >nul 2>&1 && php.exe "Server/Start.php" || goto :inst
pause
goto :EOF
:inst
title Lion Web Server --Installation[0%]
setlocal
set binary_dir=%windir:~0,3%php\bin
if  EXIST "%CD%/php.zip" goto :install
echo Checking internet connection...
ping windows.php.net >NUL
if %ErrorLevel% GTR 0 call :Err "Error: Cannot connect to the PHP official site"
echo DONE!
echo Gathering latest PHP binaries download link...
title Lion Web Server --Installation[3%]
set res=""
set pcx="64"
echo "%PROCESSOR_ARCHITECTURE%" | findstr /i "64" >NUL || set pcx="86"
::compares cpu arch (is it x32)

for /f "delims=>" %%I in ('cscript /nologo /e:jscript "%~f0" "https://windows.php.net/downloads/releases/latest"') do call :loopchecker "%%I", res
	title Lion Web Server --Installation[5%]
	echo DONE!
	cscript /nologo /e:jscript "%~f0" "%res%" "%CD%\php.zip"
	if not EXIST "%CD%/php.zip" call :Err "Try downloading php binaries from https://windows.php.net/downloads/releases/latest/"
	title Lion Web Server --Installation[50%]
	:install
	title Lion Web Server --Installation[55%]
	if not EXIST "%binary_dir%" mkdir %binary_dir%
	cscript /nologo /e:jscript "%~f0" "%CD%\php.zip" "%binary_dir%" "1"
	title Lion Web Server --Installation[98%]
	echo Writing to the 'path' variable...
path %PATH%;%binary_dir%
	echo DONE!
	echo Copying configuration...
	@COPY "%CD%\Server\php.ini" "%binary_dir%\php.ini">NUL
	if not EXIST "%binary_dir%\php.ini" call :Err "Error: Cannot copy PHP.ini"
	title Lion Web Server --Installation[100%]
	echo DONE!
	echo PHP HAS BEEN SUCCESSFULLY INSTALLED!
	echo Please, re-log into system to start-up server
	pause
	goto :EOF
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
		pause
		goto :EOF
	EXIT /B 0
goto :EOF

JScript */
function progress(position, max){
	var cr = "";
	var pos	= (position/(max/100));
	
	var msdospos = Math.round((pos/100)*40);
	var pos = Math.round(pos);
	
	for(i = 0; i < msdospos; i++)
			cr = cr + "#";
	for(i = 0; i < 40-msdospos; i++)
			cr = cr + "_"
	//WSH.Execute('title "Lion Web Server --Installation[' + pos/3.5 + ']"'); sry doesn't working
	WSH.Echo( "Downloading PHP binaries [" + liver + "]...\n" + "[" + cr + "] - " + pos + "%" + " (" + position + "Kb of " + max + "Kb)" );
};

	if(WSH.Arguments.Count() == 0) halt();
	if(WSH.Arguments.Count() < 3){
	var x = new ActiveXObject("MSXML2.XMLHTTP");
	x.open("GET",WSH.Arguments(0),true);
	x.Send(); };
if( WSH.Arguments.Count() == 1)
{
	while( x.readyState!=4 ){ WSH.Sleep(50); };
	WSH.Echo(x.responseText);
} else if( WSH.Arguments.Count() == 2) {
	var liver = WSH.Arguments(0);
	liver = (liver.split("/")[6]).split("-");
	liver = "ver " + liver[1] + '[' + liver[4] + ']';
	WSH.Echo( "Downloading PHP binaries " + liver + "..." );
	
	while( x.readyState!=4 ){ WSH.Sleep(50); };
	
	if( x.Status > 199 && x.Status < 400 ){
	var echou = "~";
	var len="Downloading PHP binaries " + liver + "...";

	for(i = 0; i<len.length; i++ )
		echou = echou + "~";
	WSH.Echo(echou);
	//Status between 200 and 399
	var objADOStream = new ActiveXObject("ADODB.Stream")
	objADOStream.Open();
	objADOStream.Type = 1; //adTypeBinary
	
	objADOStream.Write( x.ResponseBody );
	objADOStream.Position = 0;
	
		var objFSO  = new ActiveXObject("Scripting.FileSystemObject");
		if( objFSO.FileExists( WSH.Arguments(1) ) ){
			objFSO.DeleteFile( WSH.Arguments(1) );
		}
		if( objFSO.FileExists( WSH.Arguments(1) ) )
		{
			WSH.Echo("Error: file php.zip is unaccessable!");
		} else {
			objFSO = null;
			objADOStream.SaveToFile( WSH.Arguments(1) );
			objADOStream.Close();
			objADOStream = null;
			var dwlMb = x.GetResponseHeader("Content-Length")/1048576 + "";
			dwlMb = dwlMb.split(".");
			WSH.Echo("Downloaded: " + dwlMb[0] + "." + dwlMb[1].split("")[0] + "Mb =~> PHP.zip");
			WSH.Echo(echou);
		};
	} else {
		WSH.Echo("Error: cannot download latest php tarball\nAborting...");
	};
} else {
	var MyZipFile = WSH.Arguments(0);
	var MyTargetDir = WSH.Arguments(1);
	WSH.Echo("Extracting php.zip, ...");
	if( WSH.Arguments.Count() > 3 ){
		var PrompTo = WSH.Arguments(3);
	} else {
		var PrompTo = false;
	};
    
    var objShell = new ActiveXObject( "Shell.Application" );
    var x = objShell.NameSpace(MyZipFile).Items();
    var objTarget = objShell.NameSpace(MyTargetDir);

    //These are the available CopyHere options, according to MSDN
    //(http://msdn2.microsoft.com/en-us/library/ms723207.aspx).
    //     4: Do not display a progress dialog box.
    //     8: Give the file a new name in a move, copy, or rename
    //        operation if a file with the target name already exists.
    //    16: Click "Yes to All" in any dialog box that is displayed.
	var intOptions = 0;
	if( PrompTo == "false ")
		intOptions = 16;

    objTarget.CopyHere(x, intOptions);
    objTarget = null
    objShell  = null
	var objFSO  = new ActiveXObject("Scripting.FileSystemObject");
	if( objFSO.FileExists( MyTargetDir + "/php.exe" ))
	{
		WSH.Echo("PHP extracted to " + MyTargetDir);
	} else {
		WSH.Echo("Error extraction php.zip");
	}
	if( WSH.Arguments(2)=="1" ){
		objFSO.DeleteFile( MyZipFile );
	};
	objFSO = null
};
x = null;