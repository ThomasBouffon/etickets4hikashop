<?php 
jimport('joomla.installer.helper');
$installer = new JInstaller();
$pkg_path = JPATH_BASE.DS.'components'.DS.'com_hikashopeticketspackage'.DS;
error_log($pkg_path);
#$installer->_overwrite = true;

$pkgs = array( 'etickets.zip'=>'Etickets Engine',
               'hikashopeticketsdisplay.zip'=>'Etickets display'
             );

foreach( $pkgs as $pkg => $pkgname ):
  $package = JInstallerHelper::unpack( $pkg_path.$pkg );
  if( $installer->install( $package['dir'] ) )
  {
    $msgcolor = "#E0FFE0";
    $msgtext  = "$pkgname successfully installed.";
  }
  else
  {
    $msgcolor = "#FFD0D0";
    $msgtext  = "ERROR: Could not install the $pkgname. Please install manually.";
  }
  ?>
  <table bgcolor="<?php echo $msgcolor; ?>" width ="100%">
    <tr style="height:30px">
      <td width="50px"><img src="/administrator/images/tick.png" height="20px" width="20px"></td>
      <td><font size="2"><b><?php echo $msgtext; ?></b></font></td>
    </tr>
  </table>
<?php
JInstallerHelper::cleanupInstall( $pkg_path.$pkg, $package['dir'] ); 
endforeach;
