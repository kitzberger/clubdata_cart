<?php

namespace Medpzl\ClubdataCart\Domain\Model;

class Pause extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * fromdate
     *
     * @var \DateTime
     */
    protected $fromdate = null;

    /**
     * todate
     *
     * @var \DateTime
     */
    protected $todate = '';

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * Returns the fromdate
     *
     * @return \DateTime $fromdate
     */
    public function getFromdate()
    {
        return $this->fromdate;
    }

    /**
     * Sets the fromdate
     *
     * @param \DateTime $fromdate
     * @return void
     */
    public function setFromdate(\DateTime $fromdate): void
    {
        $this->fromdate = $fromdate;
    }

    /**
     * Returns the todate
     *
     * @return \DateTime todate
     */
    public function getTodate()
    {
        return $this->todate;
    }

    /**
     * Sets the todate
     *
     * @param string $todate
     * @return void
     */
    public function setTodate($todate): void
    {
        $this->todate = $todate;
    }
}
