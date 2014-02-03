<?php

namespace huitiemesens\SonataAdminGeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Helper\DialogHelper;

class createAdminCommand extends ContainerAwareCommand
{
    protected $em;
    protected $entities = array();
    protected $namespace;
    
    protected function configure()
    {
        $this
            ->setName('admin:generate')
            ->setDescription('Generate all sonata admin stuff for each entities included in a bundle')
            ->setDefinition(array(
                new InputArgument('bundle', InputArgument::OPTIONAL, 'Specify which bundle to operate'),
                new InputOption('step', null, InputOption::VALUE_NONE, 'If defined, the generation will ask for each entity generation')
            ))
            ->setHelp(<<<EOT
The <info>admin:generate</info> command generate all Sonata admin files in order to manage all entities included in a defined bundle:

  <info>php app/console doctrine:generate myBundle</info>

This interactive will generate all Sonata admin stuff included in myBundle.

EOT
            )
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ;
    }
    
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $data = explode( ':' , $input->getArgument('bundle') );
        $this->namespace = $data[0];
        $targetBundle = (!empty($data[1])) ? $data[1] : '';
        if ( $targetBundle )
        {
            $text = "Every entities included in $targetBundle will be generated";
            
            $this->em = $this->getContainer()->get('doctrine')->getEntityManager();
            //$entities = $this->em->getConfiguration()->getEntityNamespaces($bundle);
            $bundles = $this->em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
            foreach ( $bundles as $bundle )
            {
                $temp = explode ( "\\", $bundle );
                if ( $temp[1] == $targetBundle )
                {
                    $entities[] =  $temp[0].$temp[1].":".$temp[3];
                }
            }
        } 
        
        if ( !empty( $entities ) )
        {
            $text = "here is the list of entities found:" ;
            foreach ( $entities as $entity )
            {
                $text .= "\r\n -> " . $entity;
            }
            
            $output->writeln( $text );
            
            $dialog = $this->getHelperSet()->get('dialog');
            
            if ($input->isInteractive()) {
                if (!$dialog->askConfirmation($output, '<question>Do you confirm generation? [Y] Yes [N] No <question>', true)) {
                    $output->writeln('<error>Command aborted</error>');
                    return 1;
                }
                
                foreach ( $entities as $entity )
                {
                    $skip = 0;
                    $temp = explode ( ":", $entity );
                    $entityName =$temp[1];
                    $output->writeln("\r\n<info>Generation of $entity ...</info>");
                    if (!$dialog->askConfirmation($output, '<question>Do you confirm generation? [Y] Yes [N] No <question>', true)) {
                        $skip = 1;
                    }
                    
                    if ( $skip )
                    {
                        $output->writeln("<error>Skipping $entity ...</error>");
                    }else
                    {
                        $dir =  dirname($this->getContainer()->getParameter('kernel.root_dir')).'/src/'.$this->namespace."/".$targetBundle;
                        if ( is_dir ( $dir."/Admin" ) )
                        {
                            ;
                        }
                        else
                        {
                            $output->writeln( "\r\n Generating directory..." );
                            mkdir($dir."/Admin", 0755);
                        }
                            
                        $output->writeln( "\r\n Generating {$entityName}Admin.php ..." );
                        $adminFile = "<?php

namespace ".$this->namespace."\\".$targetBundle."\\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

use ".$this->namespace."\\".$targetBundle."\\".$entityName.";


class {$entityName}Admin extends Admin
{
    protected function configureFormFields(FormMapper \$formMapper)
    {
        \$formMapper
            ->with('General')";
                    $class = $this->em->getClassMetadata($this->namespace.$targetBundle.':'.$entityName);
                    foreach ( $class->fieldMappings as $field )
                    {
                        $adminFile .= "\r\n                 ->add('".$field['fieldName']."')";
                    }
                    $adminFile .= "
            ->end()
        ;
    }
    protected function configureListFields(ListMapper \$listMapper)
    {
    
        \$listMapper";
                    $i = 0;
                    foreach ( $class->fieldMappings as $field )
                    {
                        if ( $i == 0 )
                            $adminFile .= "\r\n                 ->addIdentifier('".$field['fieldName']."')";
                        else
                            $adminFile .= "\r\n                 ->add('".$field['fieldName']."')";
                        $i++;
                    }
                        $adminFile .= "\r\n                 ->add('_action', 'actions', array(
    'actions' => array(
    'view' => array(),
    'edit' => array(),
    'delete' => array(),
    )
))";
                        
                    $adminFile .= "
        ;
    }
    protected function configureDatagridFilters(DatagridMapper \$datagridMapper)
    {
        \$datagridMapper";
                    foreach ( $class->fieldMappings as $field )
                    {
                        $adminFile .= "\r\n                 ->add('".$field['fieldName']."')";
                    }
                    
                    $adminFile .= "
        ;
    }
}
?>";
    
                    file_put_contents($dir."/Admin/{$entityName}Admin.php", $adminFile);
                    $output->writeln( "\r\n Generating admin.yml ..." );
                    $adminConfig = "services:
    ".$this->namespace.".".$targetBundle.".admin.".$entityName.":
        class: ".$this->namespace."\\".$targetBundle."\\Admin\\".$entityName."Admin
        tags:
            - { name: sonata.admin, manager_type: orm, group: ".$entityName.", label: ".$entityName." }
        arguments: [ null, ".$this->namespace."\\".$targetBundle."\\Entity\\".$entityName.", SonataAdminBundle:CRUD ]
        ";
                    file_put_contents($dir."/Resources/config/admin.yml", $adminConfig, FILE_APPEND);
                    }
                }
            }
        }
    }
}
?>