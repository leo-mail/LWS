# LWS
Lion Web Server
Version 1.0

# Requirements
Linux or Windows system with pre-installed PHP interpreter or with latest PHP version support.

# Dependencies
1. PHP and PHP-CGI
2. Json Extension
3. PCRE Extension (Perl-Compatible Regex)<br>
<b>Minimal PHP version: 5.5</b>

# Version Info
Version 1.0

Supported protocols: HTTP/1.0, HTTP/1.1<br>
Supported OS: Windows 7^, Linux (Debian, Ubuntu)<br>
*The list of the supporting Operating Systems is made based on tests only.

# Installing
1) Option 1 <br>
Use one of the installers from the <a href="../../releases">releases</a> section<br>
<br> Option 2 <br>
Download content from the git repository<br> use either <a href="../../archive/master.zip">*.zip option</a> or clone it via   `git clone https://github.com/leo-mail/LWS.git`
2) Run Start.bat script<br>
*Double-click on Windows, for Linux: terminal =~> sudo bash Start.bat<br>
It will automatically identify php packages if it's already installed, if not - script will install it for you

Notice: under Windows it is possible to put php binaries in /php or /bin sub-folders

# Troubleshooting
1. The port is already taken - check for other servers running on the current machine, for example port 80 is by default blocked by Apache on Debian
2. Cannot start because port opening is prohibited - check & update your system's security settings, its security protocols maybe blocking some applications from opening TCP server ( socket connections )
3. Bugs - report any bugs to our email address or to the <a href="../../issues">Issues</a> section'

# Configuring
Open config.xml and edit values on your own (Further instructions will be written after release)

# About
Simple HTTP server, written using system functions (console scripts) and pure php code<br>
Uses CGI and PHP Streams to provide responses to the requests.
<br>Features is listed in <a href="/Features.md">Features.md<a> every release version
