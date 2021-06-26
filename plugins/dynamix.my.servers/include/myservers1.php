<!-- myservers1 -->
<style>
#header {
  z-index: 102 !important;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-pack: justify;
  -ms-flex-pack: justify;
  justify-content: space-between;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
}
vue-userprofile,
unraid-user-profile {
  font-size: 16px;
  margin-left: auto;
  height: 100%;
}

unraid-launchpad {
  position: relative;
  z-index: 10001;
}
</style>
<?
if (file_exists('/boot/config/plugins/dynamix.my.servers/myservers.cfg')) { // context needed for the UPC ENV local check for signed out users
  @extract(parse_ini_file('/boot/config/plugins/dynamix.my.servers/myservers.cfg',true));
}
// Determine what source we should use for web components
if (!file_exists('/usr/local/sbin/unraid-api')) { // When NOT using the plugin we should load the UPC from the file system unless $_COOKIE['UPC_ENV'] exists.
  $UPC_ENV = $_COOKIE['UPC_ENV'] ?? 'local';
} else { // When PLG exists load from local when not signed in but when signed in load UPC from production.
  $UPC_ENV = $_COOKIE['UPC_ENV'] ?? ((empty($remote['apikey']) || empty($var['regFILE'])) ? 'local' : 'production');
}
$upcLocalSrc = '/plugins/dynamix.my.servers/webComps/unraid.min.js'; // @NOTE - that using autov(); would render the file name below the body tag. So dont use it :(
switch ($UPC_ENV) {
  case 'production':
    $upcSrc = 'https://registration.unraid.net/webComps/unraid.min.js';
    break;
  case 'staging':
    $upcSrc = 'https://registration-dev.unraid.net/webComps/unraid.min.js';
    break;
  case 'development':
    $upcSrc = 'https://launchpad.unraid.test:6969/webComps/unraid.js';
    break;
  default: // load from webGUI filesystem.
    $upcSrc = $upcLocalSrc;
    break;
}
// add the intended web component source to the DOM
echo '<script id="unraid-wc" defer src="' . $upcSrc . '"></script>';
?>
<script type="text/javascript">
const upcEnvCookie = '<?echo $_COOKIE['UPC_ENV'] ?>';
if (upcEnvCookie) console.debug('[UPC_ENV] ✨', upcEnvCookie);
// If the UPC isn't defined after 2secs inject UPC via
setTimeout(() => {
  if (!window.customElements.get('unraid-user-profile')) {
    console.log('[UPC] Fallback to filesystem src 😖');
    const el = document.createElement('script');
    el.type = 'text/javascript';
    el.src = '<?autov($upcLocalSrc) ?>';
    return document.head.appendChild(el);
  }
  return false;
}, 2000);
function upcEnv(str) { // overwrite upc src
  const ckName = 'UPC_ENV';
  const ckDate = new Date();
  const ckDays = 30;
  ckDate.setTime(ckDate.getTime()+(ckDays*24*60*60*1000));
  console.log(`✨ ${ckName} set…reloading ✨ `);
  setTimeout(() => {
    window.location.reload();
  }, 2000);
  return document.cookie = `${ckName}=${str}; expires=${ckDate.toGMTString()}`;
};
</script>
<!-- /myservers1 -->
