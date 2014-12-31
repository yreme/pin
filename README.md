pin
===

# 基本语法

## 新建
https://github.com/yreme/pin.git
git@github.com:yreme/pin.git


此处常用到的命令
>
touch README.md
git init
git add README.md
git commit -m "first commit"
git remote add origin git@github.com:yreme/pin.git
git push -u origin master


…or push an existing repository from the command line

>
git remote add origin git@github.com:yreme/pin.git
git push -u origin master

…or import code from another repository

You can initialize this repository with code from a Subversion, Mercurial, or TFS project.


### 其他命令
让Git显示颜色，会让命令输出看起来更醒目： 
>$ git config --global color.ui true





# 常见问题


## Permission denied (publickey,gssapi-with-mic).
fatal: The remote end hung up unexpectedly

可利用 
>$ssh -vT git@github.com

进行测试，显示出debug列表。


需要把你的 ~/ssh/id_rsa.pub 放到你的服务器上的 /home/git/.ssh/authorized_keys 里头去 

看完 Github 上面写这个简单教程就会了:
http://help.github.com/set-up-git-redirect/



