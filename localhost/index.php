<HTML>
	<BODY>
		<title>Lion Web Server - Demo</title>
		<p> Hello from PHP <?php phpversion(); ?>!</p>
		<a href="/text.txt">You can visit it only on the current machine</a>
		<a href="/index">Also, you can specify whether to auto-locate *.php files or not ("check_php" option)</a>
		<a href="/dir">Dir listening??? Yes, we have</a>
		<pre>
		"
			You can add as many PHP servers as you need in the config.xml inside <HTTP> section
			You can turn off request protection by enabling proxy-site mode => <proxy>true</proxy>
			You can turn-on configuration changes auto-detection => <AutoRestart>true</AutoRestart>, after that, server will restart automatically on configuration changes
			You can specify site redirection aliases file (json or xml) => <aliases>alias.json</aliases>
			Turn on/off log saving => <full_log> option
			Limit visiting different locations with local suffix (for files) => <locallink>.suffix</locallink>
			Also, you can send some commands to the server from your local machine, using web interface
			Command address http://<ip>:<port>/?lwc=~<command>
			Commands list:
			Restart => Restart current server
			Stop	=> Stop current server
			Exit/Halt	=> Stop all servers
			RestartEvery=> Restart all servers
			Updconf		=> Update configuration manually
		"
		</pre>
	</BODY>
</HTML>