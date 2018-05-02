# Introduction

This repo contains files and commands to reproduce the demonstrations from the British Computer Society event, The Cloud, Containers & Kubernetes on the 26th April 2018.  More details of this event can be found here:

https://www.bcs.org/content/conEvent/11679

You should install Docker Desktop locally (or have access to a system with Docker installed) from [HERE](https://docs.docker.com/install/).

You should also create a public Docker Hub repository here: https://hub.docker.com/

# Sample Docker Application

The [example-docker-app](example-docker-app) directory contains a simple DockerFile to create an Ubuntu based Docker image running Apache and PHP7 and inject the simple PHP application and its content.  Note that there are many ways to construct such an image, this is perhaps not the most efficient, but does demonstrate a number of basic mechanics of DockerFiles.

First step is to build the container image, assuming you are in the root of this git repo Docker is running, you would run:
```
docker build example-docker-app  --tag alexmoore/bcs:v1
```
The `--tag` can be anything to identify this image, but because we will push it to a public Docker Hub repo it needs to be of the format `<user-name>/<repo-name>:<version>`, the rest of the instructions will reference my own repository as above, but you should substitute your own.

If you did tag your image with my own, but wanted to use your own repo, then you can simply also tag it again with:

```
docker tag alexmoore/bcs:v1 newuser/newrepo:v1
```
You can then view the built images with:
```
docker images
```
To run the container locally you can run:
```
docker run --detach --name mybcs --publish 80:80 alexmoore/bcs:v1
```
This will start a container based on that image and name it `mybcs`, detach from it and publish the containers port 80 to the localhost, you can then navigate to http://localhost (assuming you don't already run a webserver locally, in which case you may need to use a different port than 80).

If you then want to get a shell on the container for diagnostics, you can run:
```
docker exec --interactive --tty mybcs bash
```
Once you are happy that the application is running fine, you can push it up to the Docker Hub repository with the following:
```
docker push alexmoore/bcs:v1
```
And for now you can stop the container locally, everything else we'll run in the cloud, by running:
```
docker rm -f mybcs
```
This stops the container and also removes it.

# Build Kubernetes clusters on GCP & Azure

IMPORTANT:  This will cost money depending on how long the clusters run for.  Please take steps to shut down the cluster after use - see later commands. 

You can get free Google Cloud credit here: https://cloud.google.com/free/

You can get free Microsoft Azure credit here: https://azure.microsoft.com/en-gb/free/

## Google Cloud Platform

Login to the Google Cloud Platform Console : https://console.cloud.google.com

Open up the Cloud Shell and type the following command to start deployment of the cluster:

```
gcloud container clusters create bcs-cluster --cluster-version=1.9.6-gke.1 --num-nodes=1 --zone europe-west2-a --node-locations europe-west2-a,europe-west2-b,europe-west2-c
```
This will deploy a v1.9.6 Kubernetes cluster (latest at the time of writing) across 3 availability zones (in this case in London) with one Node in each region.

The version specified may change over time, to get the latest list of versions supported you can run:
```
gcloud container get-server-config --zone europe-west2-a
```
Provisioning time will take 3-5 minutes.  However once complete you can see the instances deployed in the web console or from the shell with the command:
```
gcloud compute instances list
```

## Microsoft Azure

Login to the Microsoft Azure Console : https://portal.azure.com

Open up the Cloud shell and type the following two commands to start deployment of the cluster:

```
az group create --name bcs-group --location westeurope  
az aks create --resource-group bcs-group --name bcs-cluster --node-count 3 --generate-ssh-keys --kubernetes-version 1.9.6
```
Azure resources are required to be associated with a Resource Group, so the first command creates a new empty group.  The second one created the cluster.  As before the version specified is the latest available at the time of writing, but you can se what the current available versions are by running this command:
```
az aks get-versions --location westeurope --output table
```
Total provisioning time varies, but it can be 15-20 minutes.  However once complete you can see the instances deployed in the web console or from the shell with the command:
```
az vm list
```

# Explore the Kubernetes clusters

Once the clusters have deployed, both Cloud Shell environments provide the `kubectl` command for interaction with the cluster, but you must first prime it with credentials to the new cluster, on Google you can run:
```
gcloud container clusters get-credentials bcs-cluster --zone europe-west2-a
```
And on Azure you can run:
```
az aks get-credentials --resource-group bcs-group --name bcs-cluster
```

From now on, all the commands can be run on either Kubernetes cluster.

Some basic exploratory commands:
```
kubectl cluster-info
kubectl get nodes
kubectl describe nodes
kubectl get namespaces
kubectl get events
```
These provide various information about the current state and configuration of the cluster.

Next you can run the application we created originally locally in Docker by telling the cluster to run the application by pulling the Docker image in and exposing port 80 from the Pod - this will create a new Deployment:

```
kubectl run mybcs --image=index.docker.io/alexmoore/bcs:v1 --port 80
```
Note you don't need to specify `index.docker.io/` in long form as this is the default, but this is just to show how another repository could be referenced.

You can watch the state of the container running by running:
```
kubectl get pods --watch
```
The watch command keeps the command running and updates when there are changes in state.  

The Pod is now running the application, but in order to connect to it, you will need to expose the service with this command:
```
kubectl expose deployment mybcs --type=LoadBalancer --name=bcs-service
```
Then run:
```
kubectl get services --watch
```
Wait until the `EXTERNAL-IP` field updates for the `bcs-service` entry (this can take several minutes).  In the background the cloud provider is provisioning a load balancer and a public IP, this will be the public load balancer IP that will then expose the demo application.  You can then open this IP in a browser and see the application running.

However running these steps manually is prone to error and for complex applications it can be cumbersome.  Instead you can combine these into a single manifest.

Included in this git repository is a Kubernetes Deployment Manifest called [bcs.yaml](bcs.yaml), this will create both the deployment and the service as well as configuring 3 replicas and passing through the name of the Kubernetes Node on which the current Pod is running as an environment variable so the PHP application can render it.

You will need to upload the `bcs.yaml` file to the Cloud Shell environment or simply create a new file with `vi` and paste in the contents.  Once there, you can create the new deployment with the following:
```
kubectl apply -f bcs.yaml --record
```
If you then want to make changes to the bcs.yaml file - for example change the replicas from 3 to 6, you can then re-run the same command to apply the updates.  Note the `imagePullPolicy` is set to force the download each time, this means you can have chance to see the cluster operating.  For example, run:
```
kubectl get pods
```
Pick a Pod name and run the following two commands:
```
kubectl delete pod <podname chosen>
kubectl get pods --watch
```
You will be able to see the ReplicaSet in action, as Kubernetes notes that a Pod has died and it will recreate a new one.

You can also manually scale the replicas with the following command:
```
kubectl scale --replicas=3 deployment/bcs-replica
```
And you can track changes to the deployment with the follow:
```
kubectl rollout history deployment/bcs-replica
```
Also includes in this repo is the [azure-vote.yaml](azure-vote.yaml) this is a manifest as specified in the [Azure AKS Walkthrough](https://docs.microsoft.com/en-us/azure/aks/kubernetes-walkthrough) and provides a two tier web application with REDIS backend that can also be deployed for something a bit more interesting to explore.  Again ensuring the file is available in the Cloud Shell you can deploy with 
```
kubectl apply -f azure-vote.yaml --record
```

For other useful `kubectl` commands, you can head to: https://kubernetes.io/docs/reference/kubectl/cheatsheet/

# Delete the clusters

Most important is to delete everything at the end of your experimentation on Google Cloud and Microsoft Azure to ensure your free credit use (or actual spend) is minimised.

Run the following commands, Google:
```
gcloud container clusters delete bcs-cluster --zone europe-west2-a
```
And on Azure:
```
az group delete --name bcs-group
```
This can take quite a long time to clean up all the resources, so have patience.



