<?php
namespace WebsiteForms;
if( ! current_user_can( 'activate_plugins' ) )
{
  wp_die( __( 'Not enough permissions' ) );
}
defined('ABSPATH') or die("Invalid access!");
/**
* Template Name: Blank Form template
*/

$form = Form::load(get_post(get_the_id()));
?>
<html>
<head>
    
    <meta content="<?php echo $form->title; ?>" itemprop="name" />
    <meta content="<?php echo $form->description; ?>" itemprop="description" />

    <meta content="<?php echo $form->url(); ?>" property="og:url" />
    <meta content="<?php echo $form->url(); ?>" name="twitter:url" />
    <meta content="<?php echo $form->image_url; ?>" property="og:image" />
    <meta content="<?php echo $form->image_url; ?>" name="twitter:image" />
    <meta content="<?php echo $form->title; ?>" property="og:title" />
    <meta content="<?php echo $form->title; ?>" name="twitter:title" />
    <meta content="<?php echo $form->description; ?>" property="og:description" />
    <meta content="<?php echo $form->description; ?>" name="twitter:description" />
    <meta content="@CampaignCards" name="twitter:site" />
    <meta content="summary_large_image" name="twitter:card" />
    <meta content="<?php echo $form->image_url; ?>" name="twitter:image:src" />

    <meta property="fb:app_id" value="<?php echo $form->facebook_app_id; ?>" />
    <meta content="wishpond_loc_wpool_a:campaign" property="og:type" />
  
  <style type="text/css">
    body {min-height:100vh;}
    html {margin:0; padding:0;}
    @media all{
      p, embed, object, video {min-height:0 !important; margin:0 !important;}
      iframe {margin: 0 !important;}
    }
  </style>
  <title><?php wp_title( '|', true, 'right' ); ?></title>
  <base href='<?php echo the_permalink();?>'>
  <meta charset='utf-8'>
  <meta content='width=device-width, initial-scale=1.0' name='viewport'>
  <meta content='IE=edge' http-equiv='X-UA-Compatible'>
  <meta content='notranslate' name='google'>
  <meta content='!' name='fragment'>
</head>
<body>

<?php wp_head(); ?>
<?php while (have_posts()) : the_post(); ?>
<?php the_content(); endwhile; ?>
<?php wp_footer(); ?>
</body>
</html>