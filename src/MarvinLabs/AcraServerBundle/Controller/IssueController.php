<?php

namespace MarvinLabs\AcraServerBundle\Controller;

use Doctrine\ORM\Mapping\Entity;

use Symfony\Component\HttpFoundation\Response;

use MarvinLabs\AcraServerBundle\Controller\DefaultViewController;
use MarvinLabs\AcraServerBundle\Entity\Crash;
use MarvinLabs\AcraServerBundle\DataFixtures\LoadFixtureData;

class IssueController extends DefaultViewController
{
	
	/**
	 * Render the main dashboard
	 */
	public function dashboardAction() {
		$doctrine = $this->getDoctrine()->getManager();
        $crashRepo = $doctrine->getRepository('MLabsAcraServerBundle:Crash');

		$crashes = $crashRepo->newLatestIssuesQuery()->setMaxResults(15)->getResult();
		$applicationStatistics = $crashRepo->newApplicationsStatisticsQuery()->getResult();
		$timeStatistics = $crashRepo->newApplicationsTimeStatisticsQuery()->getResult();
        
		return $this->render('MLabsAcraServerBundle:Issue:dashboard.html.twig',  $this->getViewParameters(
        		array(
						'crashes'   				=> $crashes,
        				'timeStatistics'			=> $timeStatistics,
        				'applicationStatistics'		=> $applicationStatistics
					)));
	}
	
	/**
	 * Render the dashboard for a particular app
	 */
	public function appDashboardAction($packageName) {
		$doctrine = $this->getDoctrine()->getManager();
        $crashRepo = $doctrine->getRepository('MLabsAcraServerBundle:Crash');

		$crashes = $crashRepo->newLatestIssuesQuery($packageName)->setMaxResults(15)->getResult();
		
		$versionStatistics = $crashRepo->newApplicationVersionsStatisticsQuery($packageName)->getResult();
		$androidStatistics = $crashRepo->newApplicationAndroidVersionsStatisticsQuery($packageName)->getResult();
		$timeStatistics = $crashRepo->newApplicationTimeStatisticsQuery($packageName)->getResult();
		
		return $this->render('MLabsAcraServerBundle:Issue:app_dashboard.html.twig',  $this->getViewParameters(
        		array(
						'packageName'   		=> $packageName,
        				'versionStatistics'		=> $versionStatistics,
        				'androidStatistics'		=> $androidStatistics,
        				'timeStatistics'		=> $timeStatistics,
						'crashes'   			=> $crashes
					)));
	}

	/**
	 * List the crashes corresponding to an issue
	 * 
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function issueCrashesAction($issueId)
	{		 
		$request = $this->getRequest();
		
		$crashesPerPage = $this->container->getParameter('crashes_per_page');
		$currentPage = $request->query->get('page', 1);
		
		$doctrine = $this->getDoctrine()->getManager();
		
		$rep = $doctrine->getRepository('MLabsAcraServerBundle:Crash');
		
		// Count potential results
		$crashNum = $rep->createBaseListQueryBuilder()
				->select('count(c.id)')
				->getQuery()
				->getSingleScalarResult();
		
		// Get real results
		$crashes = $rep->createBaseListQueryBuilder()
				->select(null)
				->setFirstResult(($currentPage-1)*$crashesPerPage)
				->setMaxResults($crashesPerPage)
				->getQuery()
				->getResult();
		
		return $this->render('MLabsAcraServerBundle:Crash:list.html.twig', $this->getViewParameters(
				array(
						'crashes'   	=> $crashes,
						'crashNum'		=> $crashNum,
						'page'			=> $currentPage,
						'totalPages'	=> $crashNum / $crashesPerPage
					)));
	}
	
    /**
     * Show an issue details
     */
    public function issueDetailsAction($id)
    {
        $doctrine = $this->getDoctrine()->getManager();
        $crashRepo = $doctrine->getRepository('MLabsAcraServerBundle:Crash');
        
        $issue = $crashRepo->newIssueDetailsQuery($id)->getSingleResult();
        $crashes = $crashRepo->newIssueCrashesQuery($id)->setMaxResults(15)->getResult();

        if (!$issue) {
            throw $this->createNotFoundException('Unable to find issue.');
        }

        return $this->render('MLabsAcraServerBundle:Issue:issue_details.html.twig', $this->getViewParameters(
        		array(
	            		'issue'		=> $issue,
	            		'crashes'	=> $crashes
	        		)));
    }
	
    /**
     * Show a crash details
     */
    public function crashDetailsAction($id)
    {
        $doctrine = $this->getDoctrine()->getManager();
        $crash = $doctrine->getRepository('MLabsAcraServerBundle:Crash')->find($id);

        if (!$crash) {
            throw $this->createNotFoundException('Unable to find crash.');
        }

        return $this->render('MLabsAcraServerBundle:Issue:crash_details.html.twig', $this->getViewParameters(
        		array(
	            		'crash'      => $crash,
	        		)));
    }
    
    // --------------------------------------------------------------------------------------
    // IAcraServerController implementation
    
    public function getApplications() 
    {
    	return $this->applications;	
    }

    public function setApplications($applications)
    {
    	$this->applications = $applications;
    }
    
    /** @var array */
    private $applications;
}
