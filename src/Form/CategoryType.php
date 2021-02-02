<?php


namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
           ->add('name',TextType::class)
            ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
                /** @var Category */
                $category = $event->getData();
                if (null !== $CategoryTitle = $category->getName()) {
                    $category->setSlug($this->slugger->slug($CategoryTitle)->lower());
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
