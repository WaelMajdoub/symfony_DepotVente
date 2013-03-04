<?php

namespace DepotVente\BourseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use DepotVente\BourseBundle\Entity\Article;

class GestionController extends Controller
{

	public function ficheDeposantAction($id){

		$em = $this->getDoctrine()->getManager();
        $repUser = $em->getRepository("BourseBundle:User");
        $repArticle = $em->getRepository("BourseBundle:Article");
        $repFact = $em->getRepository("BourseBundle:Facture");
        $repBourse = $em->getRepository("BourseBundle:Bourse");

        $user = $repUser->findOneBy(array("id" => $id ));
		if($user == null) {
			throw $this->createNotFoundException('Cet utilisateur n\'existe pas');
		}

		$articleVendu = $repArticle->findBy(array(
            "bourse" => $bourse = $repBourse->getCurrentBourse(),
            "sold" => true,
            "user" => $user
            ));

		$totalVendu = $repArticle->createQueryBuilder('a')
            ->select('sum(a.price)')
            ->where('a.bourse = :bourse' , 'a.user = :user' , 'a.sold = :sold ')
            ->setParameter('bourse',$bourse)
            ->setParameter('user',$user)
            ->setParameter('sold', true)
            ->getQuery()->getSingleScalarResult();
        if($totalVendu == null ){$totalVendu = 0 ;}

		$articleNonVendu = $repArticle->findBy(array(
            "bourse" => $bourse = $repBourse->getCurrentBourse(),
            "sold" => false,
            "validate" => true,
            "user" => $user
            ));

		return $this->render('BourseBundle:Gestion:ficheDeposant.html.twig',array(
			'user' => $user,
			'articleVendu' => $articleVendu,
			'articleNonVendu' => $articleNonVendu,
			"totalVendu" => $totalVendu
			));
	}

	private function formArticle($a){
		return $form = $this->createFormBuilder($a)
        ->add('name', 'text', array('label' => 'Titre de l\'article : '))
        ->add('description', 'textarea', array('label' => 'Description :'))
    	->add('price', 'number', array('label' => 'Prix :'))
        ->getForm();
	}


	public function modifArticleAction($nro){
		$request = $this->getRequest();
		$em = $this->getDoctrine()->getManager();

		$repository = $em->getRepository("BourseBundle:Article");
    	$article = $repository->findOneBy(array("nro" => $nro ));
        if($article == null) {
             throw $this->createNotFoundException('Cette article n\'existe pas');
        }

        $form = $this->formArticle($article);

        if($request->getMethod() == 'POST' ){
          	$form->bindRequest($request);
            if($form->isValid() ){
            	$article = $form->getData();
       			$em->flush();
       			return $this->redirect($this->generateUrl('bourse_article' , array ('nro' => $article->getNro())));
            }
        }

        return $this->render('BourseBundle:Gestion:modifArticle.html.twig',array(
        	'formModif' => $form->createView(),
        	'nroArticle' => $nro
        	));
	}


	public function deleteArticleAction($nro){
		$em = $this->getDoctrine()->getManager();
		$repository = $em->getRepository("BourseBundle:Article");
    	$article = $repository->findOneBy(array("nro" => $nro ));
        if($article == null) {
             throw $this->createNotFoundException('Cette article n\'existe pas');
        }
        $article->setValidate(false);
        $em->flush();
        return $this->redirect($this->generateUrl('bourse_article',array("nro" => $nro )));
	}


	public function tableauDeBordAction(){
		$em = $this->getDoctrine()->getManager();
		$repository = $em->getRepository("BourseBundle:Article");

       	return $this->render('BourseBundle:Gestion:tableaudebord.html.twig');
	}


	public function closeBourseAction(){
		$em = $this->getDoctrine()->getManager();
		$repBourse = $em->getRepository("BourseBundle:Bourse");
		$bourse = $repBourse->getCurrentBourse();
		$bourse->setOpen(false);
		$em->flush();
       	return $this->redirect($this->generateUrl('bourse_homepage'));
	}

}