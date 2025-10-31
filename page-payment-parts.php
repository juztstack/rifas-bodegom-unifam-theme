<?php
/**
* Template Name: Payment Parts Page
*
 * @package  Endrock-Theme
 * @subpackage  Timber
 * @since   Timber 0.1
*/
use Timber\Timber;
use EndrockTheme\Classes\SectionBuilderTemplates;

$template = new SectionBuilderTemplates();
$template_content = $template->get_json_template('page-upload-payments-parts');

$context = Timber::context();
$context['order'] = $template_content['order'];
$context['sections'] = $template_content['sections'];


Timber::render('templates/page-payment-parts.twig', $context);