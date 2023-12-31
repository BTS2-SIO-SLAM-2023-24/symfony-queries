<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllOrdered(): array
    {
//        $dql = 'SELECT category FROM App\Entity\Category as category ORDER BY category.name DESC';
        $qb = $this->addGroupByCategory()
            ->addOrderBy('category.name', Criteria::DESC);
        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function search(string $term): array
    {
        $termList = explode(' ', $term);
        $qb = $this->addOrderByCategoryName();

        return $this->addGroupByCategory($qb)
            ->andWhere('category.name LIKE :searchTerm OR category.name IN (:termList) OR category.iconKey LIKE :searchTerm OR fortuneCookie.fortune LIKE :searchTerm')
            ->setParameter('searchTerm', '%'.$term.'%')
            ->setParameter('termList', $termList)
            ->getQuery()
            ->getResult();
    }

    public function findWithFortunesJoin(int $id): ?Category
    {
        return $this->addFortuneCookieJoinAndSelect()
            ->andWhere('category.id = :id')
            ->setParameter('id', $id)
            ->orderBy('RAND()', Criteria::ASC)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Category $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function addGroupByCategory(QueryBuilder $qb = null): QueryBuilder
    {
        return ($qb ?? $this->createQueryBuilder('category'))
            ->addSelect('COUNT(fortuneCookie.id) AS fortuneCookiesTotal')
            ->leftJoin('category.fortuneCookies', 'fortuneCookie')
            ->addGroupBy('category.id');
    }

    private function addFortuneCookieJoinAndSelect(QueryBuilder $qb = null): QueryBuilder
    {
        return ($qb ?? $this->createQueryBuilder('category'))
            ->addSelect('fortuneCookie')
            ->leftJoin('category.fortuneCookies', 'fortuneCookie');
    }

    private function addOrderByCategoryName(QueryBuilder $qb = null): QueryBuilder
    {
        return ($qb ?? $this->createQueryBuilder('category'))
            ->addOrderBy('category.name', Criteria::DESC);
    }
}
