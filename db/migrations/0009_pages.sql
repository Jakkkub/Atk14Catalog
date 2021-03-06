CREATE SEQUENCE seq_pages;
CREATE TABLE pages (
	id INT PRIMARY KEY DEFAULT NEXTVAL('seq_pages'),
	--
	code VARCHAR(255), -- alternative key
	--
	parent_page_id INT,
	rank INT NOT NULL DEFAULT 999,
	--
	created_by_user_id INT,
	updated_by_user_id INT,
	--
	created_at TIMESTAMP NOT NULL DEFAULT NOW(),
	updated_at TIMESTAMP,
	--
	CONSTRAINT fk_pages_parent_pages FOREIGN KEY (parent_page_id) REFERENCES pages ON DELETE CASCADE,
	CONSTRAINT fk_pages_cr_users FOREIGN KEY (created_by_user_id) REFERENCES users,
	CONSTRAINT fk_pages_upd_users FOREIGN KEY (updated_by_user_id) REFERENCES users
);
CREATE UNIQUE INDEX unq_pages_code ON pages (code);
