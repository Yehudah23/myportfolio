<?php
require('config.php');

$resource = $_GET['resource'] ?? 'projects';

if($resource == 'projects'){
    $query = "SELECT * FROM `projects` ORDER BY created_at DESC";
    $result = mysqli_query($connection, $query);
    
    $projects = [];
    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            $projects[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'image' => $row['image'],
                'category' => $row['category'],
                'technologies' => json_decode($row['technologies']),
                'githubUrl' => $row['githubUrl'],
                'liveUrl' => $row['liveUrl']
            ];
        }
    }
    echo json_encode(['status' => true, 'data' => $projects]);
}

if($resource == 'skills'){
    $skills = [
        [
            'category' => 'Frontend',
            'skills' => [
                ['name' => 'HTML/CSS', 'level' => 'Expert'],
                ['name' => 'JavaScript', 'level' => 'Expert'],
                ['name' => 'Angular', 'level' => 'Advanced']
            ]
        ],
        [
            'category' => 'Backend',
            'skills' => [
                ['name' => 'PHP', 'level' => 'Expert'],
                ['name' => 'MySQL', 'level' => 'Advanced'],
                ['name' => 'Node.js', 'level' => 'Intermediate']
            ]
        ]
    ];
    echo json_encode(['status' => true, 'data' => $skills]);
}

?>
