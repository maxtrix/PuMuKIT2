<?php

namespace Pumukit\WebTVBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Adapter\DoctrineODMMongoDBAdapter;
use Pagerfanta\Pagerfanta;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;

class SearchController extends Controller
{

  /**
   * @Route("/searchseries")
   * @Template("PumukitWebTVBundle:Search:index.html.twig")
   */
  public function seriesAction(Request $request)
  {
      $numberCols = 2;
      if( $this->container->hasParameter('columns_objs_search')){
          $numberCols = $this->container->getParameter('columns_objs_search');
      }

      $this->get('pumukit_web_tv.breadcrumbs')->addList('Series search', 'pumukit_webtv_search_series');

      $searchFound = $request->query->get('search');
      $startFound = $request->query->get('start');
      $endFound = $request->query->get('end');

      $repository_series = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Series');

      $queryBuilder = $repository_series->createQueryBuilder();

      if ($searchFound != '') {
          $queryBuilder->field('$text')->equals(array('$search' => $searchFound));
      }

      if ($startFound != 'All' && $startFound != '') {
          $start = \DateTime::createFromFormat('d/m/Y', $startFound);
          $queryBuilder->field('public_date')->gt($start);
      }

      if ($endFound != 'All' && $endFound != '') {
          $end = \DateTime::createFromFormat('d/m/Y', $endFound);
          $queryBuilder->field('public_date')->lt($end);
      }

      $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

      return array('type' => 'series',
                   'objects' => $pagerfanta,
                   'number_cols' => $numberCols);
  }

  /**
   * @Route("/searchmultimediaobjects")
   * @Route("/searchmultimediaobjects/{tagCod}")
   * @Route("/searchmultimediaobjects/{tagCod}/general", defaults={"general" = true})
   * @Template("PumukitWebTVBundle:Search:index.html.twig")
   */
  public function multimediaObjectsAction($tagCod = null, $general = false, Request $request)
  {
      $numberCols = 2;
      if( $this->container->hasParameter('columns_objs_search')){
          $numberCols = $this->container->getParameter('columns_objs_search');
      }
      $this->get('pumukit_web_tv.breadcrumbs')->addList('Multimedia object search', 'pumukit_webtv_search_multimediaobjects');

      $blockedTagCod = $tagCod;
      $useBlockedTagAsGeneral = $general;
      $searchFound = $request->query->get('search');
      $tagsFound = $request->query->get('tags');
      $typeFound = $request->query->get('type');
      $durationFound = $request->query->get('duration');
      $startFound = $request->query->get('start');
      $endFound = $request->query->get('end');
      $languageFound = $request->query->get('language');

      $mmobjRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');
      $tagRepo = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag');

      $searchByTagCod = 'ITUNESU';
      if ($this->container->hasParameter('search.parent_tag.cod')) {
          $searchByTagCod = $this->container->getParameter('search.parent_tag.cod');
      }
      $parentTag = $tagRepo->findOneByCod($searchByTagCod);
      if (!isset($parentTag)) {
          throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)',$searchByTagCod));
      }

      $parentTagOptional = null;
      if( $this->container->hasParameter('search.parent_tag_2.cod')) {
          $searchByTagCod2 = $this->container->getParameter('search.parent_tag_2.cod');
          $parentTagOptional = $tagRepo->findOneByCod($searchByTagCod2);
          if( !isset($parentTagOptional)) {
              throw new \Exception(sprintf('The parent Tag with COD:  \' %s  \' does not exist. Check if your tags are initialized and that you added the correct \'cod\' to parameters.yml (search.parent_tag.cod)',$searchByTagCod));
          }
      }
      if( $blockedTagCod !== null ) {
          $tagsFound[] = $blockedTagCod;
      }
      if($tagsFound !== null) {
          $tagsFound = array_values(array_diff($tagsFound, array('All','')));
      }

      $searchLanguages = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject')->createStandardQueryBuilder()->distinct('tracks.language')->getQuery()->execute();

      $queryBuilder = $mmobjRepo->createStandardQueryBuilder();

      if ($searchFound != '') {
          $queryBuilder->field('$text')->equals(array('$search' => $searchFound));
      }
      $blockedTag = null;
      if($blockedTagCod) {
          $blockedTag = $this->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:Tag')->findOneByCod($blockedTagCod);
          if(!isset($blockedTag)) {
              throw $this->createNotFoundException(sprintf('The Tag with cod \'%s\ does not exist',$blockedTagCod));
          }
          $this->get('pumukit_web_tv.breadcrumbs')->addList($blockedTag->getTitle(), 'pumukit_webtv_search_multimediaobjects');
      }
      if (count($tagsFound) > 0) {
          $queryBuilder->field('tags.cod')->all($tagsFound);

          if($useBlockedTagAsGeneral && $blockedTag !== null ) {
              $queryBuilder->field('tags.path')->notIn(array(new \MongoRegex('/'.preg_quote($blockedTag->getPath()). '.*\|/')));
          }
      }

      if ($typeFound != '') {
          $queryBuilder->field('tracks.only_audio')->equals($typeFound == 'Audio');
      }

      if ($durationFound != '') {
          if ($durationFound == '-5') {
              $queryBuilder->field('tracks.duration')->lte(300);
          }
          if ($durationFound == '-10') {
              $queryBuilder->field('tracks.duration')->lte(600);
          }
          if ($durationFound == '-30') {
              $queryBuilder->field('tracks.duration')->lte(1800);
          }
          if ($durationFound == '-60') {
              $queryBuilder->field('tracks.duration')->lte(3600);
          }
          if ($durationFound == '+60') {
              $queryBuilder->field('tracks.duration')->gt(3600);
          }
      }

      if ($startFound != '') {
          $start = \DateTime::createFromFormat('d/m/Y', $startFound);
          $queryBuilder->field('record_date')->gt($start);
      }

      if ($endFound != '') {
          $end = \DateTime::createFromFormat('d/m/Y', $endFound);
          $queryBuilder->field('record_date')->lt($end);
      }

      if($languageFound != '') {
          $queryBuilder->field('tracks.language')->equals($languageFound);
      }

      $pagerfanta = $this->createPager($queryBuilder, $request->query->get('page', 1));

      return array('type' => 'multimediaObject',
         'objects' => $pagerfanta,
         'parent_tag' => $parentTag,
         'parent_tag_optional' => $parentTagOptional,
         'tags_found' => $tagsFound,
         'type_found' => $typeFound,
         'number_cols' => $numberCols,
         'languages' => $searchLanguages,
         'language_found' => $languageFound,
         'blocked_tag' => $blockedTag);
    }

    private function createPager($objects, $page)
    {
        $limit = 10;
        if ($this->container->hasParameter('limit_objs_search')){
            $limit = $this->container->getParameter('limit_objs_search');
        }
        $adapter = new DoctrineODMMongoDBAdapter($objects);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }
}
