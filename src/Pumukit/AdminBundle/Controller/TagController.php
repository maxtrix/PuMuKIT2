<?php

namespace Pumukit\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\AdminBundle\Form\Type\TagType;


class TagController extends Controller
{

    /**
     *
     * @Template
     */
    public function indexAction(Request $request)
    {
      $dm = $this->get('doctrine_mongodb')->getManager();
      $repo = $dm->getRepository('PumukitSchemaBundle:Tag');

      $root_name = "ROOT";
      $root = $repo->findOneByCod($root_name);

      if (null !== $root){
	$children = $root->getChildren();
      }else{
	$children = null;
      }
      
      return array('root' => $root,
		   'childrens' => $children);
    }

    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template
     */
    public function childrenAction(Tag $tag, Request $request)
    {      
      return array('tag' => $tag,
		   'childrens' => $tag->getChildren());
    }


    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     */
    public function deleteAction(Tag $tag, Request $request)
    {
      $dm = $this->get('doctrine_mongodb')->getManager();
      if(0 == $num = count($tag->getChildren())) {
	$dm->remove($tag);
	$dm->flush();
	return new JsonResponse(array("status" => "Deleted"), 200);
      }

      return new JsonResponse(array("status" => "Tag with children (" . $num .")"), 404);

    }
    
    /**
     * @ParamConverter("tag", class="PumukitSchemaBundle:Tag")
     * @Template
     */
    public function updateAction(Tag $tag, Request $request)
    {
      $dm = $this->get('doctrine_mongodb')->getManager();
      $form = $this->createForm(new TagType(), $tag);

      if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {

        $dm->persist($tag);
        $dm->flush();
	return $this->redirect($this->generateUrl('pumukitadmin_tag_index'));
      }


      return array('tag' => $tag, 'form' => $form->createView());
    }

    /**
     * @Template
     */
    public function createAction(Request $request)
    {
      $dm = $this->get('doctrine_mongodb')->getManager();

      $repo = $dm->getRepository('PumukitSchemaBundle:Tag');
      $root_name = "ROOT";
      $root = $repo->findOneByCod($root_name);

      $tag = new Tag();
      $tag->setParent($root);

      $form = $this->createForm(new TagType(), $tag);

      if (($request->isMethod('PUT') || $request->isMethod('POST')) && $form->bind($request)->isValid()) {
        $dm->persist($tag);
        $dm->flush();
	return $this->redirect($this->generateUrl('pumukitadmin_tag_index'));
      }

      return array('tag' => $tag, 'form' => $form->createView());
    }

}
