CREATE SEQUENCE seq_users START WITH 2;
CREATE TABLE users(
	id INT PRIMARY KEY DEFAULT NEXTVAL('seq_users'),
	login VARCHAR(255) NOT NULL UNIQUE,
	password VARCHAR(255),
	name VARCHAR(255),
	email VARCHAR(255),
	is_admin BOOLEAN NOT NULL DEFAULT 'f',
	registered_from_ip_addr VARCHAR(255),
	--
	updated_by_user_id INT,
	--
	created_at TIMESTAMP NOT NULL DEFAULT NOW(),
	updated_at TIMESTAMP,
	--
	CONSTRAINT fk_users_upd_users FOREIGN KEY (updated_by_user_id) REFERENCES users
);

INSERT INTO users (id,login,password,name,is_admin) VALUES(1,'admin','!to_be_replaced_by_a_hashed_password!','Charlie Root','t');
