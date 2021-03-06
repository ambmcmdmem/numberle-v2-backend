<?php

declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\ComponentRegistry;
use App\Controller\Component\CollationComponent;
use App\Controller\Component\NumberleComponent;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;

/**
 * NumberleApi Controller
 *
 * @method \App\Model\Entity\NumberleApi[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
final class NumberleApiController extends AppController
{
    private NumberleComponent $Numberle;
    private CollationComponent $Collation;

    private function getSeed(): int
    {
        return (int)$this->getRequest()->getData('seed');
    }

    private function validateRequest(): void
    {
        if (
            empty($this->getRequest()->getData('checkDigit')) ||
            (int)$this->getRequest()->getData('checkDigit') !== $this->getSeed() * 1234509876
        )
            throw new BadRequestException('不正なリクエストです。');
    }

    final public function initialize(): void
    {
        parent::initialize();
        $this->Numberle = new NumberleComponent(new ComponentRegistry());
        $this->Collation = new CollationComponent(new ComponentRegistry());
        $this->viewBuilder()->setClassName('Json');
    }

    final public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->validateRequest();
        $this->Numberle->validateSeed($this->getSeed());
    }

    final public function validateSeed(): void
    {
        $this->set('seedValid', true);
        $this->viewBuilder()->setOption('serialize', ['seedValid']);
    }

    final public function collation(): void
    {
        $this->set(
            'collation',
            $this->Collation->statusOfProposedSolution(
                $this->getRequest()->getData('proposedSolution'),
                $this->Numberle->getAnswer($this->getSeed())
            )
        );
        $this->viewBuilder()->setOption('serialize', ['collation']);
    }

    final public function answer(): void
    {
        $result = $this->fetchTable('Result')->newEntity([
            'seed' => $this->getSeed(),
            'numberOfTries' => (int)$this->getRequest()->getData('numberOfTries')
        ]);
        if (!$this->fetchTable('Result')->save($result))
            throw new BadRequestException('不正なリクエストです。');

        $this->set('answer', $this->Numberle->getAnswer($this->getSeed()));
        $this->viewBuilder()->setOption('serialize', ['answer']);
    }

    final public function totals(): void
    {
        $this->set(
            'totals',
            $this->fetchTable('Result')
                ->find('all', [
                    'conditions' => ['seed' => 22],
                    'group' => 'numberOfTries'
                ])
                ->select([
                    'numberOfTries',
                    'count' => $this->fetchTable('Result')->find()->func()->count('*')
                ])
                ->all()
        );
        $this->viewBuilder()->setOption('serialize', ['totals']);
    }
}
