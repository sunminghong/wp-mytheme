<!DOCTYPE html>
<html>
<head>
<title>博客月度统计</title>
<meta charset="utf-8" />
<base target="_blank" />

<link rel="stylesheet" href="themes/blue/style.css" type="text/css" media="print, projection, screen" />
<style>
table.posttable{
    width: 1000px;
}
table.authortable{
    width: 800px;
}
</style>

<script type="text/javascript" src="js/jquery-latest.js"></script> 
<script type="text/javascript" src="js/jquery.tablesorter.min.js"></script> 

<script>
$(document).ready(function() { 
    $(".tablesorter").tablesorter( {sortList: [[0,0], [1,0]]} ); 
});
</script>
</head>
<body>

<div>
<a href="?dd=today">本月</a> 
<?php
$mm = 8;
$tmm = intval(date('m'));
while ($mm< $tmm) {
    $yy = 2017 + intval($mm / 12);
    $imm = ($mm-1) % 12 + 1;
    //$imm = str_pad($imm,2,"0",STR_PAD_BOTH);
    $imm = $yy * 100 + $imm;
    echo '<a href="?dd='.$imm.'">'.$imm.'</a>';

    $mm ++;
} 

?>

</div>

<?php
include_once("wp-config.php");
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);


function getfields($result) {
    $fields = array();
    $i = 0;
    while ($i < mysql_num_fields($result))   
    { 
        $meta = mysql_fetch_field($result, $i);   
        $fields[$i] = $meta->name;
        $i++;  
    }  
    return $fields;
}

function showtable($result,$class) {
    echo '<table id="myTable" class="tablesorter '.$class.'">';

    if ($result) {
        $i = 0;

        $fields = getfields($result);
        echo "<thead><tr>";
        echo "<th>No.</th>";
        foreach($fields as $k) {
          echo "<th>$k</th>";
        }

        echo "</tr></thead>";

        echo "<tbody>";
        while($row = mysql_fetch_array($result))
        {
          $i ++;

          echo "<tr>";

          echo "<td>$i</td>";
          foreach($fields as $k) {
              echo "<td>".$row[$k]."</td>";
          }
          echo "</tr>";
        }
    }
    echo "</tbody></table>";
}

//$con = mysql_connect("localhost","root","abc123");
$con = mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);
if (!$con)
  {
  die('Could not connect: ' . mysql_error());
  }

//mysql_select_db("blog_wp", $con);
mysql_select_db(DB_NAME, $con);


//如果上个月的没有统计就进行统计
$sql ="select id,user_id from post_detail where yymm=date_format(date_sub(now(), interval 1 day),'%Y%m') limit 2";
$result = mysql_query($sql);
//echo $result;
if (!$result || mysql_num_rows($result) == 0) {
  echo "last month has data!";

    $sql = "
    insert into post_detail ( yymm,post_id, title, user_id,username, nicename,truename,iswrite,views,comments,loves)
     select date_format(date_sub(now(),interval 1 month),'%Y%m'),
             p.ID as post_id,
    p.post_title as title,
     u.ID as user_id,
    u.user_login as username,
    u.user_nicename as nicename,
            (select group_concat(um.meta_value separator ' ') from wp_usermeta um where um.user_id=p.post_author and (um.meta_key = 'first_name' or um.meta_key='last_name')) as truename,
    case when instr(p.post_title,'【原创')=1 then 1  else 0 end as iswrite,
    pm.meta_value as views,
    p.comment_count as comments,
    0 as loves
     from wp_posts p
            inner join wp_users u on p.post_author=u.ID 
            inner join wp_postmeta pm on pm.post_id=p.ID and pm.meta_key='views'
            
            where p.post_author>1 and p.post_type='post' and p.post_status='publish' and p.post_date between concat(date_format(date_sub(now(), interval 1 month),'%Y-%m'),'-01') and concat(date_format(now(),'%Y-%m'),'-01')

    ";
    echo mysql_query($sql);

}

$q_date = $_GET['dd'];
$q_date = mysql_real_escape_string($q_date);
echo "<p>". $q_date. "</p>";
if($q_date == "today") {

    $sql = "update wp_posts set post_title=replace(post_title,'【分享】','【转载】') where post_title like '【分享】%' and post_date > concat(date_format(now(), Y-%m'),'-01') ";
    mysql_query($sql);

    $sql = "
    select user_id ,concat('<a href=\"/p/author/',username, '\">',nicename,'(',truename,')</a>') as author ,
 sum(iswrite)  as writes ,count(*) - sum(iswrite) as copys, sum(comments) as comments,sum(views) as views from 
    (
     select u.ID as user_id,
    u.user_login as username,
    u.user_nicename as nicename,
            (select group_concat(um.meta_value separator ' ') from wp_usermeta um where um.user_id=p.post_author and (um.meta_key = 'first_name' or um.meta_key='last_name')) as truename,
    case when instr(p.post_title,'【原创')=1 then 1  else 0 end as iswrite,
    pm.meta_value as views,
    p.comment_count as comments,
    0 as loves
     from wp_posts p
            inner join wp_users u on p.post_author=u.ID 
            inner join wp_postmeta pm on pm.post_id=p.ID and pm.meta_key='views'
            
            where p.post_author>1 and p.post_type='post' and p.post_status='publish' and p.post_date between concat(date_format(now(),'%Y-%m'),'-01') and concat(date_format(date_sub(now(), interval -1 month),'%Y-%m'),'-01')

    ) a

    group by user_id,username,nicename,truename
     order by writes desc,views desc,copys desc
";

    $data1=mysql_query($sql);

    $sql = "
     select concat('<a href=\"p/',p.ID,'\">',p.post_title,'</a>') as title,
     u.ID as user_id,
            (select group_concat(um.meta_value separator ' ') from wp_usermeta um where um.user_id=p.post_author and (um.meta_key = 'first_name' or um.meta_key='last_name')) as truename,
    case when instr(p.post_title,'【原创')=1 then 1  else 0 end as iswrite,
    pm.meta_value as views,
    p.comment_count as comments,
    0 as loves
     from wp_posts p
            inner join wp_users u on p.post_author=u.ID 
            inner join wp_postmeta pm on pm.post_id=p.ID and pm.meta_key='views'
            
            where p.post_author>1 and p.post_type='post' and p.post_status='publish' and p.post_date between concat(date_format(now(),'%Y-%m'),'-01') and concat(date_format(date_sub(now(), interval -1 month),'%Y-%m'),'-01')
order by iswrite desc,views desc ,comments desc
    ";
    $data2=mysql_query($sql);
} else {

    $sql = "
select user_id, concat('<a href=\"/p/author/',username, '\">',nicename,'(',truename,')</a>') as author, sum(iswrite)  as writes ,count(*) - sum(iswrite) as copys, sum(comments) as comments,sum(views) as views from 
post_detail where yymm=${q_date} group by user_id,username,nicename,truename
 order by writes desc,views desc,copys desc
";
    $data1 = mysql_query($sql);
    

    $sql = "select concat('<a href=\"p/',post_id,'\">',title,'</a>') as title,concat('<a href=\"/p/author/',username, '\">',nicename,'(',truename,')</a>') as author,case when iswrite=1 then 'W' else 'C' end as fromwhere,views,comments,loves from post_detail where yymm=${q_date} 
        order by iswrite desc,views desc,comments desc,loves desc";
    $data2 = mysql_query($sql);

}

echo '<h3>作者统计</h3>';
showtable($data1, "authortable");
echo '<h3>文章详细</h3>';
showtable($data2,"posttable");

mysql_close($con);


/*
select u.ID, u.user_nicename, count(*) from wp_posts p inner join wp_users u on p.post_author=u.ID where post_author>1 and post_type='post' and post_status='publish' and post_date between '2017-08-01' and  '2017-09-01' group by u.ID,u.user_nicename;




select u.ID, u.user_nicename, count(*) from wp_posts p inner join wp_users u on p.post_author=u.ID where post_author>1 and post_type='post' and post_status='publish' and post_date between '2017-08-01' and  '2017-09-01' and post_title like '【原创%' group by u.ID,u.user_nicename;



select u.ID, u.user_nicename, count(*) from wp_posts p inner join wp_users u on p.post_author=u.ID where post_author>1 and post_type='post' and post_status='publish' and post_date between '2017-08-01' and  '2017-09-01' and post_title like '【转载%' group by u.ID,u.user_nicename;




select u.ID, u.user_nicename, count(*) as co, sum(comment_count) as comments from wp_posts p inner join wp_users u on p.post_author=u.ID where post_author>1 and post_type='post' and post_status='publish' and post_date between '2017-08-01' and  '2017-09-01' and post_title like '【原创%' group by u.ID,u.user_nicename;




insert into post_detail (ID, yymm,post_id, title, user_id,nicename,truename,iswrite,views,comments,love)
 select ,
         p.ID as post_id,
p.post_title as title,
 u.ID as user_id,
u.user_nicename,
        (select group_concat(um.meta_value separator ' ') from wp_usermeta um where um.user_id=p.post_author and (um.meta_key = "first_name" or um.meta_key='last_name')) as truename,
case when instr(p.post_title,'【原创')=1 then 1  else 0 end as iswrite,
pm.meta_value as views,
p.comment_count as comments,
0 as loves
 from wp_posts p
        inner join wp_users u on p.post_author=u.ID 
        inner join wp_postmeta pm on pm.post_id=p.ID and pm.meta_key='views'
        
        where p.post_author>1 and p.post_type='post' and p.post_status='publish' and p.post_date between '2017-08-01' and  '2017-09-01'  
        


 select date_format(now(),'%Y-%m'),
         p.ID as post_id,
p.post_title as title,
 u.ID as user_id,
u.user_nicename,
        (select group_concat(um.meta_value separator ' ') from wp_usermeta um where um.user_id=p.post_author and (um.meta_key = "first_name" or um.meta_key='last_name')) as truename,
case when instr(p.post_title,'【原创')=1 then 1  else 0 end as iswrite,
pm.meta_value as views,
p.comment_count as comments,
0 as loves
 from wp_posts p
        inner join wp_users u on p.post_author=u.ID 
        inner join wp_postmeta pm on pm.post_id=p.ID and pm.meta_key='views'
        
        where p.post_author>1 and p.post_type='post' and p.post_status='publish' and p.post_date between concat(date_format(now(),'%Y-%m'),'-01') and concat(date_format(date_sub(now(), interval -30 day,'%Y-%m'),'-01')




select user_id, user_nicename, count(*) as posts,sum(iswrite)  as writes ,count(*) - sum(iswrite) as copys, sum(comment_count) as comments,sum(views) as views from (
        
       ) a  group by user_id,user_nicename;



CREATE TABLE `post_detail` (
  `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `yymm` bigint(20) unsigned NOT NULL default '0',
  `post_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',

  `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `username` varchar(32) NOT NULL DEFAULT '',
  `nicename` varchar(32) NOT NULL DEFAULT '',
  `truename` varchar(32) NOT NULL default '',
  `iswrite` bigint(20) NOT NULL DEFAULT '0',
  `views` bigint(20) NOT NULL DEFAULT '0',
  `comments` bigint(20) NOT NULL DEFAULT '0',
  `loves` bigint(20) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `yymm` (`yymm`),
  Key `user_id` (`user_id`),
  key `usename`(`username`),
  key `truename`(`truename`),
  key `iswrite`(`iswrite`),
  key `views`(`views`),
  key `comments`(`comments`),
  key `loves`(`loves`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 


*/

?>
</body>
