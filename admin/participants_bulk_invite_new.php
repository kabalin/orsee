<?php
// part of orsee. see orsee.org
ob_start();
$jquery=array('popup');
$title="bulk_invite_new_participants";
$menu__area="participants_bulk_invite_new";
include ("header.php");
if ($proceed) {
    $allow=check_allow('participants_bulk_invite_new');
}

if ($proceed) {
    echo '<center>';

    echo '<FORM action="participants_invite_new.php" method="POST">';

        $posted_query=array('query'=> array(0=> array("statusids_multiselect"=>array("not"=>"", "ms_status"=>"0"))));
        $query_array=query__get_query_array($posted_query['query']);
        $query=query__get_query($query_array,0,array(),'creation_time DESC',false);

    echo '<BR>
        <TABLE width="80%" border="0">
        <TR><TD colspan="2">';

    //$emails=query_show_query_result($query,"participants_unconfirmed",false);

    echo '</TD></TR></TABLE>';
    echo '</FORM>';

    echo '<BR><BR>'.button_link('participants_main.php',lang('back'),'level-up').'<BR><BR>';

    echo '</CENTER>';

}
include ("footer.php");
?>
