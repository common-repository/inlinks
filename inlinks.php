<?php
/*
Plugin Name: In Link
Plugin URI: http://www.vikaskedia.com/inlink/
Description: There had always been a scarcity of WP plugins that would perform simple tasks. Here we present InterLinking - a simple tool ..s helps you to link a specific keyword with respective URL throughout the blog. 

This will help when you want to emphasise on a few specific pages for specific terms. A simple example: set the keyword ..Contact Us .? and use the URL: http://yourdomain.com/contact-us/. Now every time you use the ..Contact Us .? - it will get linked to your contact page. 

More importantly, this plugin works well to link to external websites.
Version: 1.0
Author: Vikas Kedia
Author URI: http://www.vikaskedia.com/
*/

/*
  Copyright 2007 George Notaras <gnot [at] g-loaded.eu>, CodeTRAX.org

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      http://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

/*

INTERNAL Configuration Options
*/
$include_keywords_in_single_posts = TRUE;
register_activation_hook(__FILE__,'table_install'); 

/*
Translation Domain

Translation files are searched in: wp-content/plugins
*/
load_plugin_textdomain('urlkeywordmapping', 'wp-content/plugins');

/*
Admin Panel
*/   
$Message = "";
global $wpdb;
$table_name = $wpdb->prefix ."URLKeywordsMapping";
$SelectURLKeywordsMappingInArray=  "select * from $table_name where FldDeleted!=1 order by FldKeyword asc " ;
$URLKeywordsMappingInArray = $wpdb->get_results($SelectURLKeywordsMappingInArray);   
                                                            
$MappingRecordCount = count($URLKeywordsMappingInArray);
                             
if(isset($_POST['Add']) && $_POST['Add'] == 'Add')
{     
    $Keyword = trim($_POST['keyword']);    
    $URL = trim($_POST['url']);
    $Rel = trim($_POST['rel']);
    $Target = trim($_POST['target']); 
    $table_name = $wpdb->prefix ."URLKeywordsMapping";
    $SelectKeywordURLMappingDetails = "select * from $table_name where FldKeyword LIKE '".$Keyword."'" ;
                                                        
    $KeywordURLMappingDetails = $wpdb->get_results($SelectKeywordURLMappingDetails);   
    
    if(count($KeywordURLMappingDetails))
    {
        $Message = "<div align='center' style=\"color:red; font-weight:bold;\">The keyword <i>".$Keyword."</i> already exists in the table.</div>";
    }                                                                                                                     
    else
    {                              
        $DataForInsert = array(
                            'FldURL' => $URL,
                            'FldKeyword' => $Keyword,
                            'FldDeleted'=>0,
                            'Rel'=> $Rel ,
                            'Target'=> $Target
                            );
        $table_name = $wpdb->prefix ."URLKeywordsMapping";
        $wpdb->insert( $table_name, $DataForInsert, array( '%s', '%s','%d','%s','%s' ) ) ;  
        $pArgumentsInArray = array('ID' => $wpdb->insert_id) ;
        $ListOfURLKeywordsMappingInArray = FnReturnListOfURLKeywordsMappingInArray($pArgumentsInArray); 
        $MappingRecordCount = sizeof($ListOfURLKeywordsMappingInArray); 
        if($MappingRecordCount > 0)    {
        foreach($ListOfURLKeywordsMappingInArray as $URLKeywordsMapping )
        {
                                                               
            $pArgArr = array(
                                'keyword' => $URLKeywordsMapping->FldKeyword,
                                'URL' => $URLKeywordsMapping->FldURL,
                                'keywordUrlMappingID' => $URLKeywordsMapping->FldID,          
                                'LastPostUpdateID'=>$URLKeywordsMapping->FldLastUpdatedPostID,
                                'Rel' => $URLKeywordsMapping->Rel ,
                                'Target'=> $URLKeywordsMapping->Target,
                                'FilterPost'=>true
                            );
               
            $Stat = FnConvertKeywordsToLinkForSelectedKeywordAndURL($pArgArr);
            $Message .= " <div align='center'>Keyword <b>{$URLKeywordsMapping->FldKeyword}</b> with URL <b>{$URLKeywordsMapping->FldURL}</b> updated  successfully. <br /></div><br /><br />";
          }
        }
        $table_name = $wpdb->prefix ."URLKeywordsMapping";
        $SelectURLKeywordsMappingInArray=  "select * from $table_name where FldDeleted!=1 order by FldKeyword asc " ;
        $URLKeywordsMappingInArray = $wpdb->get_results($SelectURLKeywordsMappingInArray); 
        
    }
      
} 
if(isset($_REQUEST['ID']) && $_REQUEST['ID']!=null){
$pArgumentsInArray = array('ID' => $_REQUEST['ID']) ;
$ListOfURLKeywordsMappingInArray = FnReturnListOfURLKeywordsMappingInArray($pArgumentsInArray);    
$MappingRecordCount = sizeof($ListOfURLKeywordsMappingInArray);  
//loop through each data and update to the table. 
foreach($ListOfURLKeywordsMappingInArray as  $URLKeywordsMapping )
{
  if(!empty($_REQUEST['type'])){
    $Message .="<div align='center'>Modified keyword for <b>{$URLKeywordsMapping->FldKeyword}</b> and removed internal link</div><br /><br />";
    $pArgArray = array(
                            'Keywords'=> $URLKeywordsMapping->FldKeyword,
                            'URL'=>$URLKeywordsMapping->FldURL,
                            'BoolRegex'=>true,
                            );    
    $PostIDandPostTextDetailsInArray = FnGetIDAndTextInArrayForKeywordsinPostText($pArgArray);
    $strRel ='';
      
    if(!empty($URLKeywordsMapping->Rel)){$strRel ="rel=\"{$URLKeywordsMapping->Rel}\"";}
    if(!empty($URLKeywordsMapping->Target)){$strTarget ="target=\"{$URLKeywordsMapping->Target}\"";}
    foreach($PostIDandPostTextDetailsInArray as $val2)
    {
      
        $PostTextToUpdate=$val2->post_content;
        $ArgumentsInArray=array(
                                    'StringToFind'=>"<a href=\"{$URLKeywordsMapping->FldURL}\" $strRel $strTarget>{$URLKeywordsMapping->FldKeyword}</a>",
                                    'Keyword'=> $URLKeywordsMapping->FldKeyword,
                                    'PostTextToUpdate'=> $PostTextToUpdate
                                );
               
        $UpdatedText['post_content']= FnRemoveUrlMappingFromKeyword($ArgumentsInArray);
        $table_name = $wpdb->prefix ."posts";   
        $update_sql = "UPDATE $table_name SET post_content = '".addslashes($UpdatedText['post_content'])."'
                        WHERE ID = '".$val2->ID."'" ;
        $wpdb->query($update_sql);
        
 
    }
        $table_name = $wpdb->prefix ."URLKeywordsMapping";   
        $wpdb->query("
                      DELETE FROM $table_name WHERE FldID='".$URLKeywordsMapping->FldID."'");

        echo "<meta http-equiv=refresh content=0;url=options-general.php?page=inlinks.php>"  ;
           
   } 
  else{                                                          
        $pArgArr = array(
                            'keyword' => $URLKeywordsMapping['FldKeyword'],
                            'URL' => $URLKeywordsMapping['FldURL'],
                            'keywordUrlMappingID' => $URLKeywordsMapping['FldID'],          
                            'LastPostUpdateID'=>$URLKeywordsMapping['FldLastUpdatedPostID'],
                            'Rel' => $URLKeywordsMapping['Rel'] ,
                            'Target'=> $URLKeywordsMapping['Target'],
                            'FilterPost'=>true
                        );
        $Stat = FnConvertKeywordsToLinkForSelectedKeywordAndURL($pArgArr); 
        $Message .= " <div align='center'>Keyword <b>{$URLKeywordsMapping['FldKeyword']}</b> with URL <b>{$URLKeywordsMapping['FldURL']}</b> updated  successfully. <br /></div><br /><br />";
   }   
}                                                                                                    


//echo $MessageText;


} 
$table_db_version = "1.0";

function table_install () {
   global $wpdb;
   global $table_db_version;

   $table_name = $wpdb->prefix . "URLKeywordsMapping";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
     $sql = "CREATE TABLE IF NOT EXISTS ".$table_name." (
              `FldID` int(11) NOT NULL auto_increment,
              `FldURL` varchar(255) NOT NULL,
              `FldKeyword` varchar(255) NOT NULL,
              `FldNotes` longtext NOT NULL,
              `FldLastUpdatedPostId` mediumint(8) NOT NULL default '0',
              `FldDeleted` tinyint(1) NOT NULL default '1',
              `Rel` varchar(255) NOT NULL,
              `Target` varchar(255) NOT NULL,
              PRIMARY KEY  (`FldID`));";
      
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
      add_option("jal_db_version", $table_db_version);

   }
}

function FnReturnListOfURLKeywordsMappingInArray($pArgumentsInArray){
           global $wpdb; 
           $table_name = $wpdb->prefix ."URLKeywordsMapping";
           $WhereCond = (isset($pArgumentsInArray['ID']) && $pArgumentsInArray['ID']!=null)?" FldID={$pArgumentsInArray['ID']} and FldDeleted!=1":" FldDeleted!=1";
           $SelectURLKeywordsMappingInArray = "select `FldID` , `FldURL` , `FldKeyword` , `FldLastUpdatedPostID` , `FldDeleted`, `Rel`,`Target` from $table_name where $WhereCond order by LENGTH(`FldKeyword`) DESC" ;   
           $ListOfURLKeywordsMappingInArray = $wpdb->get_results($SelectURLKeywordsMappingInArray);
           return $ListOfURLKeywordsMappingInArray  ;
}
function FnRemoveUrlMappingFromKeyword($pArgumentsInArray){  
            $UpdatedText=str_replace($pArgumentsInArray['StringToFind'],$pArgumentsInArray['Keyword'],$pArgumentsInArray['PostTextToUpdate']);
            return $UpdatedText;
        } 
function Url_Keyword_Mapping_add_pages() {
    add_options_page(__('In Links Options', 'urlkeywordmapping'), __('In Links Options', 'urlkeywordmapping'), 8, __FILE__, 'Url_Keyword_Mapping_options_page');
}

function Url_Keyword_Mapping_options_page_show_info_msg($msg) {
    echo '<div id="message" class="updated fade"><p>' . $msg . '</p></div>';
}

function Url_Keyword_Mapping_options_page() {
    if (isset($_POST['info_update'])) {
      /*
        For a little bit more security and easier maintenance, a separate options array is used.
      */

      //var_dump($_POST);
        $options = array(
            "site_description"    => $_POST["site_description"],
            "site_keywords"        => $_POST["site_keywords"],
            "site_wide_meta"    => $_POST["site_wide_meta"],
            );
        update_option("add_meta_tags_opts", $options);
        Url_Keyword_Mapping_options_page_show_info_msg(__('Add-Meta-Tags options saved.', 'add-meta-tags'));

    } elseif (isset($_POST["info_reset"])) {

        delete_option("add_meta_tags_opts");
        Url_Keyword_Mapping_options_page_show_info_msg(__('Add-Meta-Tags options deleted from the WordPress database.', 'add-meta-tags'));

        /*
        The following exists for deleting old add-meta-tags options (version 1.0 or older).
        The following statement have no effect if the options do not exist.
        This is 100% safe (TM).
        */
        delete_option('Url_Keyword_Mapping_options_page_site_description');
        delete_option('Url_Keyword_Mapping_options_page_site_keywords');

    } else {

        $options = get_option("add_meta_tags_opts");

    }

    /*
    Configuration Page
    */
        
    //  global $MappingRecordCount;
    global $URLKeywordsMappingInArray;
    $MappingRecordCount = count($URLKeywordsMappingInArray);
    global $Message;
    print('
          <div class="wrap">
        <h2>'.__('Url Keyword Mapping Options', 'urlkeywordmapping').'</h2>
       
    </div>
            <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" dir="ltr" lang="en">
        <head>
           <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
           <title>URL Keywords Mapping Details</title>
           <link rel="stylesheet" type="text/css" href="files/Cpanel.css">
           <script language=javascript>
            function isValidURL() {
              var url = document.getElementById("url").value;
            
                    var urlRegxp = /^(http:\/\/(www)?|https:\/\/(www)?)/;
                    if (urlRegxp.test(url) != true) {
                        alert("Please enter a valid url");
                        return false;
                    } else {
                        return true;
                    }
                }
           </script>
        </head>
        <body>
        <br />
        <center><font class="titlemainheading"><b>URL Keywords Mapping</b></font></center> <br />
        <div align="center">
        <form action="" method="post" OnSubmit="return isValidURL();">
        <table width="50%">
        <tr class="tableheading">
        <th align="right">Keyword: </th>
        <td><input type="text" name="keyword" size="30"></td>
        </tr>
        <tr>
        <th align="right">URL:</th>
        <td> <input type="text" id="url" name="url" size="50"></td>
        </tr>
        <tr>
        <th align="right">REL:</th>
        <td><SELECT NAME=rel>
             <OPTION value="" selected>None</OPTION>
             <OPTION value="nofollow">nofollow</OPTION>
             <OPTION value="bookmark">bookmark</OPTION>   
                                                       
           </SELECT></td>
        </tr>
                <tr>
        <th align="right">Target:</th>
        <td><SELECT NAME=target>
             <OPTION value="" selected>None</OPTION>
             <OPTION value="_blank">_blank</OPTION>    
             <OPTION value="_self">_self</OPTION>
             <OPTION value="_parent">_parent</OPTION> 
             <OPTION value="_top">_top</OPTION> 
            </SELECT></td>
        </tr>
        <tr class="tablerow">
        <td colspan="2" align="center"><input type="hidden" name="ActionType" value="AddKeywordURL"><input type="submit" name="Add" value="Add" /></td>
        </tr>
          

        <!--<tr><td colspan="3"><input type="submit" name="Update" value="Update All" /></td></tr>-->
        </table>
        </form></div>
        <br /> 
        </body>
        </html>');   
        echo "<hr />".$Message."<br>"; 
        if($MappingRecordCount > 0)
          {
        echo '<div class="wrap"><table align="center" border="1" class="tablestyle" cellpadding="2" cellspacing="1" width="60%">
                <tr align="center" class="tablemainheading"><th>KeyWord</th><th>URL</th><th>UpdateEntry</th><th>ClearEntry</th></tr>';
        foreach($URLKeywordsMappingInArray as $URLKeywordsMappingValues)
        {
            echo '<tr class="tablerow" align="left"><td align="center">'.$URLKeywordsMappingValues->FldKeyword.'</td>';
            echo '<td align="center">'.$URLKeywordsMappingValues->FldURL.'</td>';
            echo "<td align='center'>Updated Entries</a></td>";
            echo "<td align='center'><a href='options-general.php?page=inlinks.php&type=clear&ID={$URLKeywordsMappingValues->FldID}'>Clear Entries</a></td></tr>";
        }
        echo "</table></div>";
    }
    
    // <a href='options-general.php?page=urlkeywordmapping.php&ID={$URLKeywordsMappingInArray[$Counter]['FldID']}'

}




/*
Actions
*/


function FnGetIDAndTextInArrayForKeywordsinPostText($pArgumentsInArray)
        { 
           global $wpdb; 
           $table_name = $wpdb->prefix ."posts";
           $WhereCond = "";              
                        
           if(isset($pArgumentsInArray['BoolRegex']) && $pArgumentsInArray['BoolRegex']==true)
                $WhereCond .= " ppt.post_content REGEXP '".$pArgumentsInArray['Keywords']."'";
           else
                $WhereCond .= " ppt.post_content LIKE('%".$pArgumentsInArray['Keywords']."%')";
           
          $SelectSql = "select ppt.ID,ppt.post_author, ppt.post_content from $table_name ppt where $WhereCond order by ppt.ID asc" ;
     
          $IDandPostTextDetailsInArray=$wpdb->get_results($SelectSql); 
                             
          return $IDandPostTextDetailsInArray;
        }
 
  
function FnConvertKeywordsIntoLinkWithinAText($pArgumentsInArray)
        { 
          $keyword =  $pArgumentsInArray['keyword'] ;
          $url = $pArgumentsInArray['URL']; 
          $rel = $pArgumentsInArray['Rel'];  
          $target = $pArgumentsInArray['Target'];  
          $strRel = '';
          $strTarget ='' ;
      //  $pArgumentsInArray['msgText']=$ArrayList1['ModifiedPostTextArray'];
          for ($PostTextCounter=0; $PostTextCounter < count($pArgumentsInArray['msgText']); $PostTextCounter++)
             {
                 $replCount = 0;
                 $message   =$pArgumentsInArray['msgText'][$PostTextCounter]['post_content'];
         // $message = str_ireplace($keyword, "<a href=$url>$keyword</a>", $message);
                 if(!empty($rel)){ $strRel = "rel=\"$rel\"" ; }
                 if(!empty($target)){ $strTarget = "target=\"$target\"";}
                 
                 $message = preg_replace("/\b{$keyword}\b/i","<a href=\"$url\" $strRel $strTarget>$keyword</a>",$message) ;
                 $pArgumentsInArray['msgText'][$PostTextCounter]['post_content']=$message;
             }
                 
        return $pArgumentsInArray['msgText'];
        }
function FnConvertKeywordsToLinkForSelectedKeywordAndURL($pArgumentsInArray)
        {
        global $wpdb ;
        $pArgArray=array
            (
            'keywords' => $pArgumentsInArray['keyword'],
            'URL'      => $pArgumentsInArray['URL'],
            'Rel'      => $pArgumentsInArray['Rel'],
            'Target'   => $pArgumentsInArray['Target']
            );
   
        $IDandPostTextDetailsInArray=FnGetIDAndTextInArrayForKeywordsinPostText($pArgArray);
        if(count($IDandPostTextDetailsInArray) > 0){
                     $Counter = 0;
                foreach($IDandPostTextDetailsInArray as  $IDandPostTextDetailsValues)
                    {
                    $pArgumentsInArray['msgText'][$Counter]['ID']  = $IDandPostTextDetailsValues->ID;
                   $pArgumentsInArray['msgText'][$Counter]['post_content']=$IDandPostTextDetailsValues->post_content;
                    ++$Counter ; 
                    }
        }
             
        $ConvertedPostTextArray        = FnConvertKeywordsIntoLinkWithinAText($pArgumentsInArray);
        $table_name = $wpdb->prefix ."posts";  
        for ($Counter=0; $Counter < count($ConvertedPostTextArray); $Counter++)
            {
            if ($ConvertedPostTextArray[$Counter] != "-99999")
                {
                  $update_sql ="UPDATE $table_name SET post_content ='".addslashes($ConvertedPostTextArray[$Counter]['post_content'])."'
                            WHERE ID = '".$ConvertedPostTextArray[$Counter]['ID']."'";
                  $wpdb->query($update_sql);
                }
            }
        $Msg = "converted successfully"; 
        return $Msg;
        }  
        add_action('admin_menu', 'Url_Keyword_Mapping_add_pages');
//   add_action('wp_head', 'Url_Keyword_Mapping_options_page_add_meta_tags', 0);                                            
?>
