<?php

// This mod was fixed by Whiteneo due it have many issues in original plugin file.

function awardgranting_menu(&$sub_menu)
{
    global $mybb;
    
    end($sub_menu);
    $key = (key($sub_menu))+10;
        
    if(!$key)
    {
        $key = '160';
    }
        
    $sub_menu[$key] = array('id' => 'awardgranting', 'title' => 'Auto Award Granting', 'link' => "index.php?module=tools-awardgranting");    
}

function awardgranting_action_handler(&$action)
{
    $action['awardgranting'] = array('active' => 'awardgranting', 'file' => 'awardgranting.php');
}

function awardgranting_admin()
{
    global $page, $mybb;
    if($page->active_action != "awardgranting")
    {
        return;
    }
    $page->add_breadcrumb_item("Automatic Award Granting");
} 

function awardgranting_add_table()
{
    global $db, $mybb;
    $db->write_query("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."awardgranting` (
  `id` int(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
  `awardid` int(10) NOT NULL, 
  `actype` varchar(50) NOT NULL,
  `shouts` TEXT NULL,
  `acvalue` int(10) NOT NULL)
  ");
} 

function awardgranting_remove_table()
{
    global $db, $mybb;
    $query = "DROP TABLE `".TABLE_PREFIX."awardgranting`";
    $db->query($query);
}

function awardgranting_dogrant()
{
    global $db, $mybb;
    if ($mybb->user['uid'] != 0 or $mybb->user['usergroup'] != 7 or $mybb->user['usergroup'] != 1)
        $date = time() + 8440;
        $datefix = time() - 8440;
        $db->update_query("ougc_awards_users", array("date" => $datefix), "date>{$date}");
        $tasks = $db->query("SELECT * FROM ".TABLE_PREFIX."awardgranting");
        while ($task = $db->fetch_array($tasks))
        {
            $already = 0;
            $reason = "";
            $reached = 0;
            $awardid = $task['awardid'];
            $actype = $task['actype'];
            $acvalue = $task['acvalue'];
            $fids = explode(",", $mybb->settings['fids']);
            $query = $db->simple_select("dvz_shoutbox", "COUNT(*) AS shouts", "uid='{$mybb->user['uid']}'");
            $shouts = my_number_format($db->fetch_field($query, "shouts"));
            if (empty($fids))
            {
                $newposts = $mybb->user['postnum'];
            }
            else
            {
                $newposts = 0;
                foreach($fids as $fid)
                {
                    $query = $db->simple_select("posts", "COUNT(*) AS newposts", "visible='1' AND uid='{$mybb->user['uid']}' AND fid='$fid'");
                    $newposts = $newposts + my_number_format($db->fetch_field($query, "newposts"));
                }
            }
            switch($actype)
            {
                case 'onlinetime':
                    $acvalue = nice_time($acvalue);
                    if ($mybb->user['timeonline'] >= $acvalue)
                    {
                        $acvalue = trim($acvalue);
                        $reason = "Reached $acvalue of online time";
                        $reached = 1;
                    }
                    break;
                case 'posts':
                    if ($newposts >= $acvalue)
                    {
                        $reason = "Reached $acvalue posts";
                        $reached = 1;
                    }
                    break;
                case 'reputation':
                    if ($mybb->user['reputation'] >= $acvalue)
                    {
                        $reason = "Reached $acvalue reputations points";
                        $reached = 1;
                    }
                    break;
                case 'shouts' :
                    if ($shouts >= $acvalue)
                    {
                        $reason = "Reached $acvalue shouts";
                        $reached = 1;
                    }
                case 'newpoints':
                    if ($mybb->user['newpoints'] >= $acvalue)
                    {
                        $reason = "Reached $acvalue points";
                        $reached = 1;
                    }
                    break;
            }
            $own_awards = $db->query("SELECT * FROM ".TABLE_PREFIX."ougc_awards_users WHERE uid = {$mybb->user['uid']}");
            while ($own_award = $db->fetch_array($own_awards))
            {
                if ($own_award['aid'] == $awardid)
                {
                    $already = $already + 1;
                }
            }
            if ($already == 0 and $reached == 1)
            {
                $uid = (int)$mybb->user['uid'];
                $date = time();
                $db->query("INSERT INTO ".TABLE_PREFIX."ougc_awards_users(uid, aid, reason, date) VALUES({$uid}, {$awardid}, '{$reason}', {$date})");
            }
            $already = 0;
            $reason = "";
            $reached = 0;
        }
}